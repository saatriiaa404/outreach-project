<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OutreachType;
use App\Models\User;

/**
 * Master Type is managed by the ADMIN only (D-040). A Type does not change
 * the form flow, so new Types may be added (D-057).
 */
class OutreachTypePolicy
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

    public function update(User $user, OutreachType $type): bool
    {
        return $user->role === UserRole::Admin;
    }
}
