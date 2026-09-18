<?php

namespace App\Http\Controllers\Api;

use App\Enums\CreatorApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreatorApplicationRequest;
use App\Http\Resources\CreatorApplicationResource;
use App\Models\CreatorApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Asking to be trusted with the catalogue.
 *
 * One open application per cook: applying again while a decision is still
 * pending rewrites the pitch the reviewer will read rather than queueing a
 * second copy of the same person. A decline closes that application, so the
 * next attempt starts a new row and the earlier one stays on file.
 */
class CreatorApplicationController extends Controller
{
    public function store(StoreCreatorApplicationRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validated();

        $application = CreatorApplication::updateOrCreate(
            ['user_id' => $user->id, 'status' => CreatorApplicationStatus::Pending],
            ['pitch' => $validated['pitch'], 'youtube_channel_url' => $validated['youtube_channel_url'] ?? null],
        );

        return response()->json([
            'message' => 'Thank you. We read every application, and you will hear back here.',
            'data' => new CreatorApplicationResource($application),
        ], 201);
    }

    /**
     * The attempt that counts, so the page can say "we have your application"
     * or hand back the note that came with a decline. Null when a cook has
     * never applied, which is the state the form is for.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $application = $user->latestCreatorApplication()->first();

        return response()->json([
            'data' => $application instanceof CreatorApplication ? new CreatorApplicationResource($application) : null,
        ]);
    }
}
