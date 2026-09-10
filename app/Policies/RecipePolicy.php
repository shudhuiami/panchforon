<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Authorization for recipe moderation in the admin panel.
 *
 * Mirrors UserPolicy: the panel middleware is the first gate, this is the
 * second, and Filament consults it for every page and record action.
 */
class RecipePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Recipe $recipe): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Recipes arrive from TheMealDB imports or user submissions.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Approving and unpublishing.
     *
     * Restricted to user submissions: imports are not user generated, and
     * unpublishing one would drift the catalogue from the upstream import.
     */
    public function moderate(User $user, Recipe $recipe): bool
    {
        return $this->isActiveAdmin($user) && $recipe->isUserSubmitted();
    }

    /**
     * Bulk moderation. Per-record eligibility is still checked while applying,
     * so a selection mixing imports and submissions only affects submissions.
     */
    public function moderateAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
