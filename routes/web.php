<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/**
 * SPA entry point. Everything that is not an API route or the Filament admin
 * panel falls through to the React application.
 */
Route::get('/{any?}', SpaController::class)->where('any', '^(?!api$|api/|admin$|admin/).*$');
