<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Only the ADMIN marks archives (D-038).
 */
class ArchiveRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isActive() ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
