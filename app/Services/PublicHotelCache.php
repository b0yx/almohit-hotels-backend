<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;

class PublicHotelCache
{
    public static function ttl(): int
    {
        return (int) config('almohit.public_cache_ttl', 600);
    }

    public static function hotelKey(int $hotelId): string
    {
        return "public_hotel:{$hotelId}";
    }

    public static function contextKey(string $subdomain): string
    {
        return 'public_hotel_context:'.strtolower($subdomain);
    }

    public static function rememberHotel(int $hotelId, callable $resolver): array
    {
        return Cache::remember(self::hotelKey($hotelId), self::ttl(), $resolver);
    }

    public static function rememberContext(string $subdomain, callable $resolver): array
    {
        return Cache::remember(self::contextKey($subdomain), self::ttl(), $resolver);
    }

    public static function flushHotel(Hotel|int|null $hotel): void
    {
        if (! $hotel) {
            return;
        }

        if (is_int($hotel)) {
            $hotel = Hotel::query()->find($hotel);
        }

        if (! $hotel) {
            return;
        }

        Cache::forget(self::hotelKey((int) $hotel->id));

        foreach (array_filter([
            $hotel->subdomain,
            $hotel->getOriginal('subdomain'),
        ]) as $subdomain) {
            Cache::forget(self::contextKey((string) $subdomain));
        }
    }

    public static function flushSubdomain(?string $subdomain): void
    {
        if (! $subdomain) {
            return;
        }

        Cache::forget(self::contextKey($subdomain));
    }
}
