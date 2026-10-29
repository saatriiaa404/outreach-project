<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SpecialUnlockRequest;
use App\Models\User;

/**
 * Special Unlock requests: the requester may cancel their own, the Admin
 * works the queue (Workflow v2.1 25.2, 31.4). Creating one is
 * OutreachPolicy::requestSpecialUnlock.
 */
class SpecialUnlockRequestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isActive() ? null : false;
    }

    /**
     * The administrative request queue.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function cancel(User $user, SpecialUnlockRequest $request): bool
    {
        return $request->requester_id === $user->id;
    }

    public function process(User $user, SpecialUnlockRequest $request): bool
    {
        return $user->role === UserRole::Admin;
    }
}
