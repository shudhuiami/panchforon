<?php

namespace App\Services\Social;

/**
 * What a provider tells us about the person signing in.
 */
final class SocialProfile
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $email,
        /**
         * Whether the provider states the address is confirmed. An unverified
         * address must never be trusted to claim an existing account.
         */
        public readonly bool $emailVerified,
    ) {}
}
