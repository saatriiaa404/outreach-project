<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Accounts are created and managed by the ADMIN; there is no public
 * registration and no self-service profile change (D-014, D-044).
 */
class UserPolicy
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

    public function update(User $user, User $account): bool
    {
        return $user->role === UserRole::Admin;
    }
}
