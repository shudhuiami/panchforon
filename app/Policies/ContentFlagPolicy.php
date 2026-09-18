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
        return $user->isActiveAdmin();
    }

    public function view(User $user, ContentFlag $flag): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContentFlag $flag): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, ContentFlag $flag): bool
    {
        return $user->isActiveAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Closing a report, whether by acting on it or dismissing it.
     */
    public function resolve(User $user, ContentFlag $flag): bool
    {
        return $user->isActiveAdmin();
    }

    public function resolveAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
