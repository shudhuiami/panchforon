<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\CurrentUserResource;
use App\Http\Resources\MyRatingResource;
use App\Http\Resources\RecipeListResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

/**
 * The signed-in cook's own account: their details, their recipes (including
 * the ones still awaiting moderation) and the ratings they have left.
 */
class AccountController extends Controller
{
    /**
     * Update the name and email on the account.
     */
    public function updateProfile(UpdateProfileRequest $request): CurrentUserResource
    {
        $user = $this->user($request);
        $validated = $request->validated();

        // A new address has not been confirmed, so it starts unverified again.
        if ($validated['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return new CurrentUserResource($user->loadCount(['recipes', 'ratings']));
    }

    /**
     * Change the password. Every existing token is revoked so a session opened
     * with the old password cannot continue, and a fresh one comes back in the
     * reply so the cook making the change stays signed in on this device.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->user($request);

        $user->password = Hash::make((string) $request->validated()['password']);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Your password has been changed. Other devices have been signed out.',
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }

    /**
     * The cook's own recipes, whatever their moderation status.
     */
    public function recipes(Request $request): AnonymousResourceCollection
    {
        $recipes = $this->user($request)
            ->recipes()
            ->with('stat')
            ->latest()
            ->paginate(max(1, min($request->integer('per_page', 12), 50)));

        return RecipeListResource::collection($recipes);
    }

    /**
     * The ratings this cook has left, newest first.
     */
    public function ratings(Request $request): AnonymousResourceCollection
    {
        $ratings = $this->user($request)
            ->ratings()
            ->with(['recipe' => fn ($query) => $query->with('stat')])
            ->latest()
            ->paginate(max(1, min($request->integer('per_page', 12), 50)));

        return MyRatingResource::collection($ratings);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
