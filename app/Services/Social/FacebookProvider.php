<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

class FacebookProvider extends SocialProvider
{
    public function key(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook';
    }

    protected function authorizeEndpoint(): string
    {
        return 'https://www.facebook.com/v21.0/dialog/oauth';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://graph.facebook.com/v21.0/oauth/access_token';
    }

    /**
     * @return list<string>
     */
    protected function scopes(): array
    {
        return ['email', 'public_profile'];
    }

    protected function fetchProfile(string $accessToken): SocialProfile
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://graph.facebook.com/v21.0/me', ['fields' => 'id,name,email']);

        if (! $response->successful() || ! is_string($response->json('id'))) {
            throw new SocialAuthException('Facebook did not return an account for that sign-in.');
        }

        $email = $response->json('email');

        return new SocialProfile(
            id: (string) $response->json('id'),
            name: (string) ($response->json('name') ?: 'Cook'),
            email: is_string($email) && $email !== '' ? $email : null,
            /**
             * Facebook returns an address without saying whether it is
             * confirmed, so it is never trusted to claim an existing account.
             */
            emailVerified: false,
        );
    }
}
