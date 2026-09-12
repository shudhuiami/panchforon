<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * One OAuth2 provider. Both supported providers use the plain
 * authorization-code flow, so this is deliberately small: build a URL, then
 * trade the returned code for a profile.
 */
abstract class SocialProvider
{
    public function __construct(
        protected readonly string $clientId,
        protected readonly string $clientSecret,
        protected readonly string $redirectUri,
    ) {}

    abstract public function key(): string;

    abstract public function label(): string;

    abstract protected function authorizeEndpoint(): string;

    abstract protected function tokenEndpoint(): string;

    /**
     * @return list<string>
     */
    abstract protected function scopes(): array;

    abstract protected function fetchProfile(string $accessToken): SocialProfile;

    /**
     * Where to send the browser to start the flow.
     */
    public function authorizeUrl(string $state): string
    {
        return $this->authorizeEndpoint().'?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'state' => $state,
        ]);
    }

    /**
     * Trade the authorization code for a profile.
     */
    public function profileFromCode(string $code): SocialProfile
    {
        return $this->fetchProfile($this->exchangeCode($code));
    }

    protected function exchangeCode(string $code): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post($this->tokenEndpoint(), [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
            ]);

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            throw new SocialAuthException('The sign-in provider did not accept that response.');
        }

        return $accessToken;
    }
}
