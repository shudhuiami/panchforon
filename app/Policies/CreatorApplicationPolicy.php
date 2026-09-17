<?php

namespace App\Policies;

use App\Models\CreatorApplication;
use App\Models\User;

/**
 * Authorization for the creator application queue.
 *
 * Applications are written by members applying for themselves, never from the
 * panel, so there is no create path here.
 */
class CreatorApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function view(User $user, CreatorApplication $application): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CreatorApplication $application): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, CreatorApplication $application): bool
    {
        return $user->isActiveAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    /**
     * Closing an application, whether by approving it or declining it.
     */
    public function decide(User $user, CreatorApplication $application): bool
    {
        return $user->isActiveAdmin();
    }

    public function decideAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
