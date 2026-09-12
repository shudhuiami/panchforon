<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsRepository;
use Illuminate\Http\JsonResponse;

/**
 * The handful of site settings the storefront renders or gates on. Served
 * from the cached repository, so this is not a database read per request.
 */
class SettingsController extends Controller
{
    public function __invoke(SettingsRepository $settings): JsonResponse
    {
        return response()->json([
            'data' => [
                'site_name' => $settings->string('site_name'),
                'contact_email' => $settings->string('contact_email'),
                'registration_open' => $settings->boolean('registration_open', true),
                'submissions_open' => $settings->boolean('submissions_open', true),
            ],
        ]);
    }
}
