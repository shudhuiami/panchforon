<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Services\Social\SocialSignIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trades the one-time code from a social sign-in for a bearer token.
 */
class SocialExchangeController extends Controller
{
    public function __invoke(Request $request, SocialSignIn $signIn): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $signIn->redeemExchangeCode((string) $request->input('code'));

        if ($user === null) {
            return response()->json(['message' => 'That sign-in link has expired. Please try again.'], 422);
        }

        return response()->json([
            'user' => new CurrentUserResource($user->loadCount(['recipes', 'ratings'])),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }
}
