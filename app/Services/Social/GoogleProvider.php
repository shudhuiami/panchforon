<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

class GoogleProvider extends SocialProvider
{
    public function key(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return 'Google';
    }

    protected function authorizeEndpoint(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    /**
     * @return list<string>
     */
    protected function scopes(): array
    {
        return ['openid', 'email', 'profile'];
    }

    protected function fetchProfile(string $accessToken): SocialProfile
    {
        $response = Http::withToken($accessToken)->acceptJson()->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $response->successful() || ! is_string($response->json('sub'))) {
            throw new SocialAuthException('Google did not return an account for that sign-in.');
        }

        $email = $response->json('email');

        return new SocialProfile(
            id: (string) $response->json('sub'),
            name: (string) ($response->json('name') ?: 'Cook'),
            email: is_string($email) && $email !== '' ? $email : null,
            emailVerified: $response->json('email_verified') === true,
        );
    }
}
