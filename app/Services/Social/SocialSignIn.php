<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Turning a provider profile into a signed-in account.
 */
class SocialSignIn
{
    /** How long the one-time code handed to the browser stays usable. */
    private const CODE_TTL_SECONDS = 60;

    private const CODE_PREFIX = 'social-signin:';

    /**
     * Find or create the account behind a provider profile.
     *
     * An address is only trusted to claim an existing account when the
     * provider states it is confirmed; otherwise anyone who can set that
     * address at a lax provider could take over the account.
     */
    public function resolveUser(string $provider, SocialProfile $profile): User
    {
        $link = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $profile->id)
            ->first();

        if ($link !== null) {
            $user = $link->user;

            if ($user === null) {
                throw new SocialAuthException('That sign-in is no longer linked to an account.');
            }

            $this->refuseSuspended($user);

            return $user;
        }

        if ($profile->email === null) {
            throw new SocialAuthException('That provider did not share an email address, so we cannot create an account.');
        }

        $existing = User::query()->where('email', $profile->email)->first();

        if ($existing !== null) {
            if (! $profile->emailVerified) {
                throw new SocialAuthException('An account already uses that email. Sign in with your password first, then link this provider.');
            }

            $this->refuseSuspended($existing);
            $this->link($existing, $provider, $profile);

            return $existing;
        }

        return DB::transaction(function () use ($provider, $profile): User {
            $user = User::create([
                'name' => $profile->name,
                'email' => $profile->email,
                // There is no password to sign in with; the reset flow sets one.
                'password' => Hash::make(Str::random(64)),
            ]);

            /**
             * Verification is set outside the create call on purpose: keeping
             * it off the model's fillable list means no future mass assignment
             * can mark an address confirmed by accident.
             */
            if ($profile->emailVerified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->link($user, $provider, $profile);

            return $user;
        });
    }

    /**
     * Hand the browser a short-lived code instead of the bearer token, so no
     * long-lived credential ends up in a URL or in browser history.
     */
    public function issueExchangeCode(User $user): string
    {
        $code = Str::random(64);

        Cache::put(self::CODE_PREFIX.hash('sha256', $code), $user->id, self::CODE_TTL_SECONDS);

        return $code;
    }

    /**
     * Redeem a code exactly once.
     */
    public function redeemExchangeCode(string $code): ?User
    {
        $key = self::CODE_PREFIX.hash('sha256', $code);
        $userId = Cache::pull($key);

        if (! is_int($userId) && ! is_string($userId)) {
            return null;
        }

        $user = User::find((int) $userId);

        if ($user === null || $user->isSuspended()) {
            return null;
        }

        return $user;
    }

    private function link(User $user, string $provider, SocialProfile $profile): void
    {
        SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $profile->id],
            ['user_id' => $user->id, 'provider_email' => $profile->email],
        );
    }

    private function refuseSuspended(User $user): void
    {
        if ($user->isSuspended()) {
            throw new SocialAuthException('That account is suspended.');
        }
    }
}
