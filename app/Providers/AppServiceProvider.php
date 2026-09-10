<?php

namespace App\Providers;

use App\Services\SettingsRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /**
         * Shared so the in-memory memoization inside the repository is not
         * duplicated per injection point, and so a write invalidates the copy
         * every other caller is holding.
         */
        $this->app->singleton(SettingsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
