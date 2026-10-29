<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Accounts are created and managed by the ADMIN; there is no public
 * registration. The owner changes only their own optional profile (D-067);
 * name, email, role and status stay with the ADMIN (D-014, D-044).
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

    /**
     * Photo, phone and address: the owner or the ADMIN (D-067).
     */
    public function updateProfile(User $user, User $account): bool
    {
        return $user->id === $account->id || $user->role === UserRole::Admin;
    }

    public function viewProfile(User $user, User $account): bool
    {
        return $this->updateProfile($user, $account);
    }
}
