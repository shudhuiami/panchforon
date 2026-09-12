<?php

namespace App\Http\Controllers;

use App\Services\Social\SocialAuthException;
use App\Services\Social\SocialProviders;
use App\Services\Social\SocialSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Signing in with Google or Facebook.
 *
 * These live on the web routes because the flow needs a session to hold the
 * state parameter. The browser comes back to the single-page app with a
 * one-time code, which it trades for a bearer token over the API.
 */
class SocialAuthController extends Controller
{
    private const STATE_KEY = 'social_oauth_state';

    public function redirect(Request $request, SocialProviders $providers, string $provider): RedirectResponse
    {
        $driver = $providers->get($provider);
        abort_if($driver === null, 404);

        $state = Str::random(40);
        $request->session()->put(self::STATE_KEY, ['provider' => $provider, 'value' => $state]);

        return redirect()->away($driver->authorizeUrl($state));
    }

    public function callback(Request $request, SocialProviders $providers, SocialSignIn $signIn, string $provider): RedirectResponse
    {
        $driver = $providers->get($provider);
        abort_if($driver === null, 404);

        $stored = $request->session()->pull(self::STATE_KEY);

        if (! is_array($stored) || ($stored['provider'] ?? null) !== $provider || ! is_string($request->query('state')) || ! hash_equals((string) ($stored['value'] ?? ''), (string) $request->query('state'))) {
            return $this->failure('That sign-in could not be verified. Please try again.');
        }

        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return $this->failure('The provider cancelled that sign-in.');
        }

        try {
            $user = $signIn->resolveUser($provider, $driver->profileFromCode($code));
        } catch (SocialAuthException $e) {
            return $this->failure($e->getMessage());
        }

        return redirect('/login/social?code='.urlencode($signIn->issueExchangeCode($user)));
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect('/login/social?error='.urlencode($message));
    }
}
