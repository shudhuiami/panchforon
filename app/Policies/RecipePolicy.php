<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Authorization for recipe moderation in the admin panel, and for a creator
 * reading their own work in the studio.
 *
 * Mirrors UserPolicy: the panel middleware is the first gate, this is the
 * second, and Filament consults it for every page and record action.
 *
 * A creator reads, writes and deletes their own recipes and no one else's.
 * What stays exactly as strict as it was is moderation: a creator who could
 * moderate could approve their own recipe — or someone else's — through a
 * door meant for a moderator. Skipping the review queue is something the
 * creator role grants outright, never something a creator does by hand.
 */
class RecipePolicy
{
    /**
     * Admins list the whole catalogue in the admin panel; a creator lists
     * their own in the studio, where RecipeResource::getEloquentQuery() is
     * what narrows the rows to theirs. This only says they may see a list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin() || $this->isActiveCreator($user);
    }

    /**
     * A creator may read their own recipe and nobody else's.
     *
     * The studio's query scoping means a foreign id 404s before this is ever
     * consulted, so the ownership test here is the second gate rather than
     * the first. It is repeated anyway: the scoping is one override on one
     * resource, and this is what holds if some later screen forgets it.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        if ($user->isActiveAdmin()) {
            return true;
        }

        return $this->isActiveCreator($user) && $this->owns($user, $recipe);
    }

    /**
     * Writing a recipe is what the studio is for, so any creator whose account
     * is in good standing may. A suspended one may not: isActiveCreator()
     * refuses them here exactly as canAccessPanel() refuses them the panel.
     *
     * There is still no create screen in the admin panel — correcting and
     * moderating other people's work is what that panel does — so widening
     * this changes nothing there.
     */
    public function create(User $user): bool
    {
        return $user->isActiveAdmin() || $this->isActiveCreator($user);
    }

    /**
     * An admin may edit anything in the catalogue; a creator may edit what
     * they wrote and nothing else. Same ownership test as view(), and the
     * studio's query scoping still 404s a foreign id long before this runs.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        if ($user->isActiveAdmin()) {
            return true;
        }

        return $this->isActiveCreator($user) && $this->owns($user, $recipe);
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        if ($user->isActiveAdmin()) {
            return true;
        }

        return $this->isActiveCreator($user) && $this->owns($user, $recipe);
    }

    /**
     * Stays admin-only, and deliberately so. Filament reads this per resource
     * to decide whether to offer a delete bulk action, with no record to check
     * against, so there is no way to scope it to the rows a creator owns — the
     * studio's table therefore offers no bulk delete at all.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Approving and unpublishing.
     *
     * Restricted to user submissions: imports are not user generated, and
     * unpublishing one would drift the catalogue from the upstream import.
     */
    public function moderate(User $user, Recipe $recipe): bool
    {
        return $user->isActiveAdmin() && $recipe->isUserSubmitted();
    }

    /**
     * Bulk moderation. Per-record eligibility is still checked while applying,
     * so a selection mixing imports and submissions only affects submissions.
     */
    public function moderateAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * A creator or an admin whose account has not been suspended — the same
     * question User::canAccessPanel() asks of the studio.
     */
    protected function isActiveCreator(User $user): bool
    {
        return $user->isCreator() && ! $user->isSuspended();
    }

    /**
     * An imported recipe has no author, so a null user_id belongs to nobody
     * rather than to whoever happens to be asking.
     */
    protected function owns(User $user, Recipe $recipe): bool
    {
        return $recipe->user_id !== null
            && (int) $recipe->user_id === (int) $user->getKey();
    }
}
