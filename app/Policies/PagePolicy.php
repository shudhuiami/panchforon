<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

/**
 * Authorization for admin-written pages.
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
