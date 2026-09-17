<?php

use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/**
 * Social sign-in. These need the session, which is why they are here rather
 * than in the API routes, and they 404 until a provider is configured.
 */
Route::middleware('throttle:auth')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->where('provider', '[a-z]+');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->where('provider', '[a-z]+');
});

/**
 * SPA entry point. Everything that is not an API route or a Filament panel
 * falls through to the React application.
 *
 * The asset directories are excluded for a different reason than the panels.
 * Nothing routes them — the web server serves those files directly — but when
 * one is *missing* the request reaches PHP, and without the exclusion it would
 * land here and return the SPA's HTML with a 200. A browser asking for a
 * script then gets a page, and the only symptom is an undefined global in the
 * console. Excluding them turns a missing asset back into a plain 404.
 */
Route::get('/{any?}', SpaController::class)->where('any', '^(?!api$|api/|admin$|admin/|studio$|studio/|auth/|build/|css/|fonts/|icons/|js/).*$');
