<?php

namespace App\Support;

use App\Models\BookingInquiry;
use App\Models\Faq;
use App\Models\Hotel;
use App\Models\HotelPolicy;
use App\Models\HotelService;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CompatResponse
{
    private static function sortGalleryImages($images)
    {
        return $images->sortBy(fn ($img) => [
            ($img->is_cover ?? false) ? 0 : 1,
            $img->display_order ?? 0,
            $img->id ?? 0,
        ])->values();
    }

    private static function pickCoverImage($images)
    {
        return self::sortGalleryImages(
            $images->where('is_active', true)
        )->first();
    }

    public static function item(Model $model): array
    {
        return match (true) {
            $model instanceof User => self::user($model),
            $model instanceof Hotel => self::hotel($model),
            $model instanceof RoomType => self::roomType($model),
            $model instanceof HotelService => self::service($model),
            $model instanceof BookingInquiry => self::booking($model),
            $model instanceof Review => self::review($model),
            default => self::generic($model),
        };
    }

    public static function page(LengthAwarePaginator $page): array
    {
        return [
            'count' => $page->total(),
            'next' => $page->nextPageUrl(),
            'previous' => $page->previousPageUrl(),
            'results' => $page->getCollection()->map(fn (Model $model) => self::item($model))->values(),
        ];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'phone' => $user->phone ?? '',
            'role' => $user->isAdmin() ? 'admin' : ($user->role ?: 'customer'),
            'is_staff' => (bool) $user->is_staff,
            'is_active' => (bool) $user->is_active,
            'email_verified' => (bool) $user->email_verified,
            'permissions' => [],
            'created_at' => optional($user->created_at)->toJSON(),
        ];
    }

    public static function hotel(Hotel $hotel): array
    {
        $cover = $hotel->relationLoaded('images')
            ? self::pickCoverImage($hotel->images)
            : null;

        $avgRating = null;
        $totalReviews = 0;
        if ($hotel->relationLoaded('reviews')) {
            $active = $hotel->reviews->where('is_active', true);
            $totalReviews = $active->count();
            $avgRating = $totalReviews ? round((float) $active->avg('rating'), 1) : null;
        }

        $policy = $hotel->relationLoaded('policy') ? $hotel->policy : null;
        $socialMedia = $hotel->relationLoaded('socialMedia') ? $hotel->socialMedia : null;
        $contacts = $hotel->relationLoaded('contacts') ? $hotel->contacts : null;
        $setupStatus = $hotel->relationLoaded('setupStatus') ? $hotel->setupStatus : null;
        $amenities = $hotel->relationLoaded('amenities') ? $hotel->amenities->map(fn ($a) => self::generic($a))->values() : [];
        $images = $hotel->relationLoaded('images') ? self::sortGalleryImages($hotel->images)->map(fn ($i) => self::genericAlias($i, ['property' => 'hotel_id']))->values() : [];
        $readinessErrors = self::computeReadinessErrors($hotel);
        $faqs = self::hotelFaqs($hotel);

        return [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'subdomain' => $hotel->subdomain,
            'property_type' => $hotel->property_type,
            'country' => $hotel->country,
            'city' => $hotel->city,
            'address' => $hotel->address ?? '',
            'phone' => $hotel->phone,
            'email' => $hotel->email,
            'website' => $hotel->website,
            'stars' => $hotel->stars,
            'description' => $hotel->description ?? '',
            'short_description' => $hotel->short_description,
            'timezone' => $hotel->timezone,
            'languages_spoken' => $hotel->languages_spoken ?: [],
            'parking_available' => (bool) $hotel->parking_available,
            'airport_transfer' => (bool) $hotel->airport_transfer,
            'shuttle_service' => (bool) $hotel->shuttle_service,
            'opening_year' => $hotel->opening_year,
            'renovation_year' => $hotel->renovation_year,
            'video_url' => $hotel->video_url,
            'virtual_tour_url' => $hotel->virtual_tour_url,
            'cover_image_url' => $cover?->image,
            'average_rating' => $avgRating,
            'total_reviews' => $totalReviews,
            'amenities' => $amenities,
            'images' => $images,
            'faqs' => $faqs,
            'faq_schema' => self::faqSchema($faqs),
            'policy' => $policy ? self::genericPolicy($policy) : null,
            'social_media' => $socialMedia ? self::generic($socialMedia) : null,
            'contacts' => $contacts ? self::generic($contacts) : null,
            'setup_status' => $setupStatus ? self::generic($setupStatus) : null,
            'is_active' => (bool) $hotel->is_active,
            'publishing_status' => $hotel->publishing_status,
            'published_at' => optional($hotel->published_at)->toJSON(),
            'owner' => $hotel->owner_id,
            'readiness_errors' => $readinessErrors,
            'is_ready_to_publish' => empty($readinessErrors),
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'created_at' => optional($hotel->created_at)->toJSON(),
            'updated_at' => optional($hotel->updated_at)->toJSON(),
        ];
    }

    public static function genericPolicy(HotelPolicy $policy): array
    {
        return $policy->toArray();
    }

    private static function hotelFaqs(Hotel $hotel): array
    {
        if (! $hotel->relationLoaded('faqs')) {
            return [];
        }

        $user = request()->user();
        $isAdminRequest = $user && ($user->isAdmin() || $user->isStaffRole());
        $faqs = $hotel->faqs;
        if (! $isAdminRequest) {
            $faqs = $faqs->where('is_active', true);
        }

        return $faqs->sortBy('sort_order')->values()->map(fn (Faq $faq) => self::faq($faq, $isAdminRequest))->all();
    }

    public static function faq(Faq $faq, bool $includeAdminFields = false): array
    {
        $item = [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'question_ar' => $faq->question_ar,
            'answer_ar' => $faq->answer_ar,
            'slug' => $faq->slug,
            'meta_title' => $faq->meta_title,
            'meta_description' => $faq->meta_description,
            'meta_title_ar' => $faq->meta_title_ar,
            'meta_description_ar' => $faq->meta_description_ar,
            'canonical_url' => $faq->canonical_url,
        ];

        if ($includeAdminFields) {
            $item['sort_order'] = $faq->sort_order;
            $item['is_active'] = (bool) $faq->is_active;
        }

        return $item;
    }

    public static function faqSchema(array $faqs): ?array
    {
        $entities = collect($faqs)
            ->filter(fn (array $faq) => ! empty($faq['question']) && ! empty($faq['answer']))
            ->map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => trim(strip_tags($faq['answer'])),
                ],
            ])
            ->values()
            ->all();

        if (empty($entities)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    public static function faqPage(Hotel $hotel, Faq $faq): array
    {
        $faqData = self::faq($faq);

        return [
            'property' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'cover_image_url' => $hotel->relationLoaded('images') ? self::pickCoverImage($hotel->images)?->image : null,
            ],
            'faq' => $faqData,
            'seo' => [
                'title' => $faq->meta_title ?: $faq->question,
                'description' => $faq->meta_description ?: str($faq->answer)->stripTags()->limit(160)->toString(),
                'title_ar' => $faq->meta_title_ar ?: $faq->question_ar,
                'description_ar' => $faq->meta_description_ar ?: ($faq->answer_ar ? str($faq->answer_ar)->stripTags()->limit(160)->toString() : null),
                'canonical_url' => $faq->canonical_url,
                'robots' => 'index,follow',
            ],
            'faq_schema' => self::faqSchema([$faqData]),
        ];
    }

    public static function computeReadinessErrors(Hotel $hotel): array
    {
        $errors = [];
        if (empty($hotel->name)) {
            $errors[] = 'Property name is required.';
        }
        if (empty($hotel->slug)) {
            $errors[] = 'Property slug is required.';
        }
        if (empty($hotel->subdomain)) {
            $errors[] = 'Subdomain is required.';
        }
        if (empty($hotel->country) || empty($hotel->city)) {
            $errors[] = 'Country and city are required.';
        }
        return $errors;
    }

    public static function roomType(RoomType $room): array
    {
        $coverImageUrl = null;
        if ($room->relationLoaded('images')) {
            $cover = self::pickCoverImage($room->images);
            $coverImageUrl = $cover?->image;
        }

        return array_merge(self::genericAlias($room, ['property' => 'hotel_id']), [
            'cover_image_url' => $coverImageUrl,
            'images' => $room->relationLoaded('images') ? self::sortGalleryImages($room->images)->map(fn ($i) => self::generic($i))->values() : [],
            'prices' => $room->relationLoaded('prices') ? $room->prices->map(fn ($p) => self::generic($p))->values() : [],
            'amenity_details' => [],
        ]);
    }

    public static function service(HotelService $service): array
    {
        return array_merge(self::genericAlias($service, [
            'property' => 'hotel_id',
            'category' => 'service_category_id',
        ]), [
            'property_name' => $service->hotel?->name,
            'category_name' => $service->category?->name,
            'cover_image_url' => null,
            'images' => [],
        ]);
    }

    public static function booking(BookingInquiry $booking): array
    {
        return array_merge(self::genericAlias($booking, [
            'property' => 'hotel_id',
            'room_type' => 'room_type_id',
            'customer' => 'customer_id',
        ]), [
            'property_name' => $booking->hotel?->name,
            'room_type_name' => $booking->roomType?->name,
            'nights' => $booking->check_in && $booking->check_out ? $booking->check_in->diffInDays($booking->check_out) : null,
            'available_units_after_booking' => null,
            'guests' => $booking->relationLoaded('guests') ? $booking->guests->map(fn ($g) => self::generic($g))->values() : [],
        ]);
    }

    public static function review(Review $review): array
    {
        return self::genericAlias($review, ['property' => 'hotel_id']);
    }

    public static function generic(Model $model): array
    {
        $data = collect($model->toArray())->except(['hotel_id', 'service_category_id', 'hotel_service_id'])->all();

        if (! empty($data['icon'])) {
            $data['icon_url'] = $data['icon'];
        }

        return $data;
    }

    public static function genericAlias(Model $model, array $aliases): array
    {
        $data = $model->toArray();
        foreach ($aliases as $public => $column) {
            if (array_key_exists($column, $data)) {
                $data[$public] = $data[$column];
                unset($data[$column]);
            }
        }

        return $data;
    }
}
