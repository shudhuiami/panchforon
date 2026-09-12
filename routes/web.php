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
 * SPA entry point. Everything that is not an API route or the Filament admin
 * panel falls through to the React application.
 */
Route::get('/{any?}', SpaController::class)->where('any', '^(?!api$|api/|admin$|admin/|auth/).*$');
