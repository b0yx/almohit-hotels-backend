<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        $token = preg_match('/^(?:Bearer|Token)\s+(.+)$/i', $header, $matches) ? $matches[1] : null;

        if ($token) {
            if (str_contains($token, '|')) {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat && $pat->tokenable && $pat->tokenable->is_active) {
                    $pat->forceFill(['last_used_at' => now()])->save();
                    $request->setUserResolver(fn () => $pat->tokenable);
                }
            } else {
                $record = ApiToken::query()->with('user')->where('token', hash('sha256', $token))->first();
                if ($record && $record->user && $record->user->is_active) {
                    $record->forceFill(['last_used_at' => now()])->save();
                    $request->setUserResolver(fn () => $record->user);
                }
            }
        }

        return $next($request);
    }
}
