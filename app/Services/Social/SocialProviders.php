<?php

namespace App\Services\Social;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Builds the configured providers. A provider with no client id simply does
 * not exist as far as the rest of the application is concerned.
 */
class SocialProviders
{
    /** @var array<string, class-string<SocialProvider>> */
    private const PROVIDERS = [
        'google' => GoogleProvider::class,
        'facebook' => FacebookProvider::class,
    ];

    public function __construct(private readonly Config $config) {}

    public function get(string $key): ?SocialProvider
    {
        $class = self::PROVIDERS[$key] ?? null;

        if ($class === null) {
            return null;
        }

        $clientId = $this->config->get("services.{$key}.client_id");
        $clientSecret = $this->config->get("services.{$key}.client_secret");

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            return null;
        }

        return new $class($clientId, $clientSecret, url("/auth/{$key}/callback"));
    }

    /**
     * The providers a deployment has switched on, for the sign-in screen.
     *
     * @return list<array{key: string, label: string}>
     */
    public function enabled(): array
    {
        $enabled = [];

        foreach (array_keys(self::PROVIDERS) as $key) {
            $provider = $this->get($key);

            if ($provider !== null) {
                $enabled[] = ['key' => $provider->key(), 'label' => $provider->label()];
            }
        }

        return $enabled;
    }
}
