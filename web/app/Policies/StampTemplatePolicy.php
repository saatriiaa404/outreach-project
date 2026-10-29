<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Stamp templates are managed by the ADMIN (database.md 10.3).
 */
class StampTemplatePolicy
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
