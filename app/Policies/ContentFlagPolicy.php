<?php

namespace App\Policies;

use App\Models\ContentFlag;
use App\Models\User;

/**
 * Authorization for the report queue.
 *
 * Reports are written by the community through the API, never from the panel,
 * so there is no create path here.
 */
class ContentFlagPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, ContentFlag $flag): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContentFlag $flag): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, ContentFlag $flag): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    /**
     * Closing a report, whether by acting on it or dismissing it.
     */
    public function resolve(User $user, ContentFlag $flag): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function resolveAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
