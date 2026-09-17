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
        return $user->isActiveAdmin();
    }

    public function view(User $user, Page $page): bool
    {
        return $user->isActiveAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActiveAdmin();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->isActiveAdmin();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->isActiveAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActiveAdmin();
    }
}
