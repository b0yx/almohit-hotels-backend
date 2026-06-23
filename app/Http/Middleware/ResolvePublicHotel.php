<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicHotel
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->requestedSubdomain($request);
        $request->attributes->set('public_hotel_subdomain', $subdomain);
        $request->attributes->set('public_hotel', null);
        $request->attributes->set('public_hotel_status', 'bypass');

        if ($subdomain) {
            $hotel = Hotel::query()->where('subdomain', $subdomain)->first();
            if (! $hotel) {
                $request->attributes->set('public_hotel_status', 'invalid');
            } elseif (! $hotel->is_active || $hotel->publishing_status !== 'published') {
                $request->attributes->set('public_hotel_status', 'unavailable');
            } else {
                $request->attributes->set('public_hotel', $hotel);
                $request->attributes->set('public_hotel_status', 'available');
            }
        }

        return $next($request);
    }

    private function requestedSubdomain(Request $request): ?string
    {
        $base = config('almohit.public_base_domain', 'almohit.com');
        $reserved = config('almohit.reserved_subdomains', []);
        $host = strtolower(preg_replace('/:\d+$/', '', $request->getHost()));
        $candidate = null;

        if (str_ends_with($host, '.'.$base)) {
            $label = substr($host, 0, -strlen('.'.$base));
            if ($label !== '' && ! str_contains($label, '.')) {
                $candidate = $label;
            }
        }

        $candidate = $candidate ?: strtolower(trim((string) $request->header('X-Hotel-Subdomain')));
        $candidate = rtrim($candidate, '.');

        if (! $candidate || in_array($candidate, $reserved, true)) {
            return null;
        }

        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $candidate) ? $candidate : null;
    }
}
