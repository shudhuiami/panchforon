<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

/**
 * Authorization for the units table.
 *
 * Who may touch it, and nothing else: whether a particular unit can be removed
 * is a question about recipe lines rather than about the person, and it is
 * answered by UnitActions::delete() so the admin is told which lines are in the
 * way instead of finding the action missing.
 */
class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Units are never deleted in bulk; each one has to be checked for the
     * recipe lines written in it first.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
