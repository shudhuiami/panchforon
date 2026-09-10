<?php

use Illuminate\Support\Facades\Route;

/**
 * SPA entry point. Everything that is not an API route or the Filament admin
 * panel falls through to the React application.
 */
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api$|api/|admin$|admin/).*$');
