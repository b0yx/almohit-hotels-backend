<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $thresholdMs = (int) config('almohit.slow_api_log_threshold_ms', 500);

        if ($thresholdMs > 0 && $durationMs >= $thresholdMs) {
            Log::warning('Slow API response', [
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
