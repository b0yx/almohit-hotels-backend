<?php

namespace App\Support;

use App\Models\BookingInquiry;
use App\Models\Hotel;
use App\Models\HotelService;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CompatResponse
{
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
            'created_at' => optional($user->created_at)->toJSON(),
        ];
    }

    public static function hotel(Hotel $hotel): array
    {
        $cover = method_exists($hotel, 'coverImage') ? $hotel->coverImage() : null;

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
            'average_rating' => round((float) $hotel->reviews()->where('is_active', true)->avg('rating'), 1) ?: null,
            'total_reviews' => $hotel->reviews()->where('is_active', true)->count(),
            'amenities' => $hotel->relationLoaded('amenities') ? $hotel->amenities->map(fn ($a) => self::generic($a))->values() : [],
            'images' => $hotel->relationLoaded('images') ? $hotel->images->map(fn ($i) => self::genericAlias($i, ['property' => 'hotel_id']))->values() : [],
            'policy' => null,
            'social_media' => null,
            'contacts' => null,
            'setup_status' => null,
            'is_active' => (bool) $hotel->is_active,
            'publishing_status' => $hotel->publishing_status,
            'published_at' => optional($hotel->published_at)->toJSON(),
            'owner' => $hotel->owner_id,
            'readiness_errors' => [],
            'is_ready_to_publish' => true,
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'created_at' => optional($hotel->created_at)->toJSON(),
            'updated_at' => optional($hotel->updated_at)->toJSON(),
        ];
    }

    public static function roomType(RoomType $room): array
    {
        return array_merge(self::genericAlias($room, ['property' => 'hotel_id']), [
            'cover_image_url' => $room->images()->where('is_active', true)->orderByDesc('is_cover')->value('image'),
            'images' => $room->relationLoaded('images') ? $room->images->map(fn ($i) => self::generic($i))->values() : [],
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
        return collect($model->toArray())->except(['hotel_id', 'service_category_id', 'hotel_service_id'])->all();
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
