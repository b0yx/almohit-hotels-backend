<?php

namespace App\Providers;

use App\Console\Commands\ServeCommand;
use Illuminate\Foundation\Console\ServeCommand as FrameworkServeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FrameworkServeCommand::class, ServeCommand::class);
    }

    public function boot(): void
    {
        foreach ([
            storage_path('app/public/hotels'),
            storage_path('app/public/room-types'),
            storage_path('app/public/facilities'),
            storage_path('app/public/amenities'),
            storage_path('app/public/facility-categories'),
            storage_path('app/tmp'),
        ] as $directory) {
            if (! is_dir($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
        }
    }
}
