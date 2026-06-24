<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AvailabilityBlock;
use App\Models\BookingInquiry;
use App\Models\ContactMessage;
use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Models\HotelImage;
use App\Models\HotelService;
use App\Models\Review;
use App\Models\RoomPrice;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    private static array $contentTypeMap = [
        User::class => 'user',
        Hotel::class => 'hotel',
        BookingInquiry::class => 'booking',
        Review::class => 'review',
        RoomType::class => 'room_type',
        RoomPrice::class => 'room_price',
        HotelAmenity::class => 'hotel_amenity',
        HotelService::class => 'hotel_service',
        ServiceCategory::class => 'service_category',
        AvailabilityBlock::class => 'availability_block',
        ContactMessage::class => 'contact_message',
        HotelImage::class => 'hotel_image',
        RoomTypeImage::class => 'room_type_image',
        ServiceImage::class => 'service_image',
    ];

    public static function contentTypeFor(string $modelClass): string
    {
        return self::$contentTypeMap[$modelClass]
            ?? str_replace('_', '-', snake_case(class_basename($modelClass)));
    }

    public static function log(
        string $action,
        string $contentType,
        Model|string|int|null $entity = null,
        ?array $changes = null,
        ?string $objectId = null,
        ?string $objectRepr = null,
    ): AuditLog {
        $request = request();
        $user = $request?->user();

        if ($entity instanceof Model) {
            $objectId ??= (string) $entity->getKey();
            $objectRepr ??= (string) (
                $entity->name
                ?? $entity->email
                ?? $entity->customer_name
                ?? $entity->full_name
                ?? $entity->title
                ?? $entity->getKey()
            );
        }

        return AuditLog::query()->create([
            'action' => $action,
            'content_type' => $contentType,
            'object_id' => $objectId ?? '',
            'object_repr' => $objectRepr ?? '',
            'actor_id' => $user?->id,
            'actor_email' => $user?->email ?? '',
            'actor_name' => $user?->full_name ?? '',
            'changes' => $changes,
            'request_method' => $request?->method() ?? '',
            'request_path' => $request?->path() ?? '',
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }

    public static function changes(?Model $old, array $data): ?array
    {
        if (!$old) {
            return null;
        }
        $changes = [];
        foreach ($data as $key => $value) {
            $oldValue = $old->getOriginal($key);
            if ($oldValue !== $value) {
                $changes[$key] = ['old' => $oldValue, 'new' => $value];
            }
        }
        return empty($changes) ? null : $changes;
    }
}
