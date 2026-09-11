<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorization for the admin panel's user management.
 *
 * The panel's Authenticate middleware already blocks non-admins at the HTTP
 * boundary. This policy is the second layer: Filament resources consult it for
 * every page and record action, so a component reached any other way is still
 * refused.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Accounts come from people registering, never from the panel.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isActiveAdmin($user) && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Granting admin, suspending, and lifting a suspension.
     *
     * Refused on your own account so nobody can revoke their own access or
     * suspend themselves out of the panel.
     */
    public function moderate(User $user, User $model): bool
    {
        return $this->isActiveAdmin($user) && $user->isNot($model);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
