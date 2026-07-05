<?php

namespace App\Providers;

use App\Console\Commands\ServeCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand as FrameworkServeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FrameworkServeCommand::class, ServeCommand::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('almohit.rate_limits.api_per_minute', 300))
                ->by($this->rateLimitKey($request));
        });

        RateLimiter::for('public-read', function (Request $request) {
            return Limit::perMinute((int) config('almohit.rate_limits.public_read_per_minute', 180))
                ->by($this->rateLimitKey($request));
        });

        RateLimiter::for('booking-write', function (Request $request) {
            return Limit::perMinute((int) config('almohit.rate_limits.booking_write_per_minute', 10))
                ->by($this->rateLimitKey($request));
        });

        foreach ([
            storage_path('app/public/hotels'),
            storage_path('app/public/room-types'),
            storage_path('app/public/facilities'),
            storage_path('app/public/facility-categories'),
            storage_path('app/tmp'),
        ] as $directory) {
            if (! is_dir($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }

    private function rateLimitKey(Request $request): string
    {
        return $request->user()
            ? 'user:'.$request->user()->id
            : 'ip:'.($request->ip() ?: 'unknown');
    }
}
