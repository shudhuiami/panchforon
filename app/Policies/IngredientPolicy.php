<?php

namespace App\Policies;

use App\Models\Ingredient;
use App\Models\User;

/**
 * Authorization for the ingredient dictionary.
 */
class IngredientPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Ingredient $ingredient): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Ingredient $ingredient): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Ingredient $ingredient): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Folding one ingredient into another.
     */
    public function merge(User $user, Ingredient $ingredient): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
