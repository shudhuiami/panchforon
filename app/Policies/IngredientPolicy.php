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
        return $user->isActiveAdmin();
    }

    public function view(User $user, Ingredient $ingredient): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Ingredient $ingredient): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, Ingredient $ingredient): bool
    {
        return $user->isActiveAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Folding one ingredient into another.
     */
    public function merge(User $user, Ingredient $ingredient): bool
    {
        return $user->isActiveAdmin();
    }
}
