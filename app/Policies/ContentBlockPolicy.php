<?php

namespace App\Policies;

use App\Models\ContentBlock;
use App\Models\User;

/**
 * Authorization for the editable storefront strings.
 *
 * The set of blocks is defined in code, so the panel edits them but never
 * creates or removes one: a stray key would render nowhere, and a missing one
 * would silently fall back to the built-in wording.
 */
class ContentBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function view(User $user, ContentBlock $block): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContentBlock $block): bool
    {
        return $this->isActiveAdmin($user);
    }

    public function delete(User $user, ContentBlock $block): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_admin && ! $user->isSuspended();
    }
}
