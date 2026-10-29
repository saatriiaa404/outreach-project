<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OutreachSource;
use App\Models\User;

/**
 * Master Source is managed by the ADMIN only (D-040). The three baseline
 * Sources are fixed because each defines its own form flow and PDF package
 * (Workflow v2.1 9.1 to 9.3); they can be renamed and (de)activated, not
 * added (D-057).
 */
class OutreachSourcePolicy
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
        return false;
    }

    public function update(User $user, OutreachSource $source): bool
    {
        return $user->role === UserRole::Admin;
    }
}
