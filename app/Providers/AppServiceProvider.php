<?php

namespace App\Providers;

use App\Models\Unit;
use App\Services\SettingsRepository;
use App\Support\UnitRegistry;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        /**
         * The units table is what a unit means, for everything downstream of a
         * recipe line. It is read on nearly every shopping list and written to
         * almost never, so it is cached and handed round as one object; the
         * Unit model drops that cache whenever a row changes.
         */
        $this->app->singleton(
            UnitRegistry::class,
            fn (): UnitRegistry => UnitRegistry::fromDefinitions(Unit::cachedDefinitions()),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Credential endpoints get a tight per-IP budget so a password list
         * cannot be replayed against them. Everything else keeps the default.
         */
        /**
         * The reset link has to open the single-page app, not a Blade route.
         */
        ResetPassword::createUrlUsing(
            fn (object $notifiable, string $token): string => url('/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset()))
        );

        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
    }
}
