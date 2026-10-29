<?php

namespace App\Models;

use App\Enums\SpecialUnlockRequestStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A request for Special Unlock (Database v1.1 13.2). It never changes the
 * Outreach status; the Admin is the executor, not a separate approver.
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $requester_id
 * @property UserRole $requester_role_snapshot
 * @property string $reason
 * @property SpecialUnlockRequestStatus $status
 * @property int|null $admin_id
 * @property string|null $processed_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $processed_at
 * @property-read Outreach $outreach
 * @property-read User $requester
 * @property-read User|null $admin
 */
class SpecialUnlockRequest extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requester_role_snapshot' => UserRole::class,
            'status' => SpecialUnlockRequestStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Outreach, $this>
     */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
