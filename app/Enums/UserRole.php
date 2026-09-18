<?php

namespace App\Enums;

enum UserRole: string
{
    /**
     * Someone who cooks from the catalogue. They may still submit recipes, but
     * every submission goes through the moderation queue before anyone else
     * sees it. This is the default for every account, so it is the safe one.
     */
    case Member = 'member';

    /**
     * A cook we have decided to trust with the catalogue. They write straight
     * into it and get the studio to manage what they have written, which is
     * the whole point of separating them from members.
     */
    case Creator = 'creator';

    /**
     * Runs the place. An admin is a creator plus the moderation queue, the
     * settings and everyone else's content, so anything a creator may do an
     * admin may do as well.
     */
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Creator => 'Creator',
            self::Admin => 'Administrator',
        };
    }

    /**
     * Filament colour token used for badges in the admin panel.
     */
    public function color(): string
    {
        return match ($this) {
            self::Member => 'gray',
            self::Creator => 'info',
            self::Admin => 'danger',
        };
    }

    /**
     * Whether a recipe by this role skips the moderation queue.
     *
     * Trust is the difference between the roles: we read creators and admins
     * before granting the role, so reading their recipes afterwards buys us
     * nothing. Members are unvetted, so their submissions still queue.
     */
    public function publishesWithoutReview(): bool
    {
        return $this === self::Creator || $this === self::Admin;
    }

    /**
     * Whether this role reaches the creator studio.
     *
     * The studio exists to manage recipes you publish yourself, so it is only
     * useful to the roles that can publish. Admins keep it because the studio
     * is about their own recipes, not the queue they moderate for others.
     */
    public function reachesStudio(): bool
    {
        return $this === self::Creator || $this === self::Admin;
    }
}
