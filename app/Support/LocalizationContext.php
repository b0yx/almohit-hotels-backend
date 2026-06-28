<?php

namespace App\Support;

use Illuminate\Http\Request;

class LocalizationContext
{
    public static function getLocale(?Request $request = null): string
    {
        $req = $request ?: request();

        $locale = $req->header('X-Locale')
            ?? $req->query('locale')
            ?? $req->header('Accept-Language');

        if ($locale && str_starts_with(strtolower(trim($locale)), 'ar')) {
            return 'ar';
        }

        return 'en';
    }

    public static function isArabic(?Request $request = null): bool
    {
        return self::getLocale($request) === 'ar';
    }
}
