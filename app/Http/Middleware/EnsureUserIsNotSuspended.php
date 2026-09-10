<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests from suspended accounts.
 *
 * Blocking suspension at login alone would not be enough: Sanctum tokens
 * issued before the suspension keep working until they expire. This runs
 * after auth:sanctum so an admin suspending an account revokes access on the
 * account's very next request.
 */
class EnsureUserIsNotSuspended
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isSuspended()) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'reason' => $user->suspension_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
