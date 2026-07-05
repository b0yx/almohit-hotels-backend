<?php

namespace App\Support;

use App\Models\BookingInquiry;
use App\Models\Facility;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Faq;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\HotelPolicy;
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
            $model instanceof Currency => self::currency($model),
            $model instanceof ExchangeRate => self::exchangeRate($model),
            $model instanceof Hotel => self::hotel($model),
            $model instanceof RoomType => self::roomType($model),
            $model instanceof Facility => self::facility($model),
            $model instanceof BookingInquiry => self::booking($model),
            $model instanceof Review => self::review($model),
            $model instanceof Favorite => self::favorite($model),
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

    public static function currency(Currency $currency): array
    {
        return [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'symbol_position' => $currency->symbol_position,
            'decimal_places' => $currency->decimal_places,
            'thousand_separator' => $currency->thousand_separator,
            'decimal_separator' => $currency->decimal_separator,
            'is_default' => (bool) $currency->is_default,
            'is_active' => (bool) $currency->is_active,
            'created_at' => optional($currency->created_at)->toJSON(),
            'updated_at' => optional($currency->updated_at)->toJSON(),
        ];
    }

    public static function exchangeRate(ExchangeRate $rate): array
    {
        return [
            'id' => $rate->id,
            'from_currency_id' => $rate->from_currency_id,
            'from_currency' => $rate->relationLoaded('fromCurrency') && $rate->fromCurrency ? self::currency($rate->fromCurrency) : null,
            'to_currency_id' => $rate->to_currency_id,
            'to_currency' => $rate->relationLoaded('toCurrency') && $rate->toCurrency ? self::currency($rate->toCurrency) : null,
            'exchange_rate' => (string) $rate->exchange_rate,
            'effective_from' => optional($rate->effective_from)->toJSON(),
            'effective_to' => optional($rate->effective_to)->toJSON(),
            'source' => $rate->source,
            'notes' => $rate->notes,
            'is_manual' => (bool) $rate->is_manual,
            'created_by' => $rate->created_by,
            'created_at' => optional($rate->created_at)->toJSON(),
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
        $images = $hotel->relationLoaded('images') ? self::sortGalleryImages($hotel->images)->map(fn ($i) => self::genericAlias($i, ['property' => 'hotel_id']))->values() : collect();
        $facilities = $hotel->relationLoaded('facilities') ? $hotel->facilities->map(fn (Facility $facility) => self::facility($facility))->values() : collect();
        $readinessErrors = self::computeReadinessErrors($hotel);
        $faqs = self::hotelFaqs($hotel);

        $data = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'name_ar' => $hotel->name_ar,
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
            'details' => $hotel->details ?? '',
            'description_ar' => $hotel->description_ar,
            'short_description' => $hotel->short_description,
            'short_description_ar' => $hotel->short_description_ar,
            'meta_title' => $hotel->meta_title,
            'meta_description' => $hotel->meta_description,
            'meta_title_ar' => $hotel->meta_title_ar,
            'meta_description_ar' => $hotel->meta_description_ar,
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
            'facility_ids' => $facilities->pluck('id')->values(),
            'facilities' => $facilities,
            'amenities' => $facilities,
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
            'is_favorite' => (bool) ($hotel->is_favorite ?? false),
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'created_at' => optional($hotel->created_at)->toJSON(),
            'updated_at' => optional($hotel->updated_at)->toJSON(),
        ];

        return LocalizedMapper::mapOutput($hotel, $data);
    }

    public static function genericPolicy(HotelPolicy $policy): array
    {
        $data = LocalizedMapper::mapOutput($policy, $policy->toArray());

        foreach (['check_in_from', 'check_in_to', 'check_out_from', 'check_out_to'] as $field) {
            $data[$field] = self::formatPolicyTime($data[$field] ?? null);
        }

        return $data;
    }

    private static function formatPolicyTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
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

        $data = array_merge(self::genericAlias($room, ['property' => 'hotel_id']), [
            'cover_image_url' => $coverImageUrl,
            'images' => $room->relationLoaded('images') ? self::sortGalleryImages($room->images)->map(fn ($i) => self::generic($i))->values() : [],
            'prices' => $room->relationLoaded('prices') ? $room->prices->map(fn ($p) => self::generic($p))->values() : [],
        ]);

        return LocalizedMapper::mapOutput($room, $data);
    }

    public static function facility(Facility $facility): array
    {
        $data = self::genericAlias($facility, [
            'category' => 'facility_category_id',
        ]);

        $data = array_merge($data, [
            'category_name' => $facility->category?->name,
            'property_ids' => $facility->relationLoaded('hotels') ? $facility->hotels->pluck('id')->values() : [],
            'properties' => $facility->relationLoaded('hotels') ? $facility->hotels->map(fn (Hotel $hotel) => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
            ])->values() : [],
            'cover_image_url' => null,
            'images' => $facility->relationLoaded('images') ? self::sortGalleryImages($facility->images)->map(fn ($i) => self::genericAlias($i, ['facility' => 'facility_id']))->values() : [],
        ]);

        return LocalizedMapper::mapOutput($facility, $data);
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

    public static function favorite(Favorite $favorite): array
    {
        $data = [
            'id' => $favorite->id,
            'user_id' => $favorite->user_id,
            'hotel_id' => $favorite->hotel_id,
            'created_at' => optional($favorite->created_at)->toJSON(),
        ];

        if ($favorite->relationLoaded('hotel')) {
            $data['hotel'] = self::hotel($favorite->hotel);
        }

        return $data;
    }

    public static function generic(Model $model): array
    {
        $data = collect($model->toArray())->except(['hotel_id', 'facility_category_id', 'facility_id'])->all();

        if (! empty($data['icon'])) {
            $data['icon_url'] = $data['icon'];
        }

        return LocalizedMapper::mapOutput($model, $data);
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

        return LocalizedMapper::mapOutput($model, $data);
    }
}
