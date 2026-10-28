<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * In-app notification of a main workflow event (Database v1.1 12.1). It is
 * not a source of truth; queues follow status and the latest version.
 * Named to avoid confusion with Laravel's notification system.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property int $outreach_id
 * @property int $outreach_version_id
 * @property int|null $approval_id
 * @property Carbon|null $read_at
 */
class InAppNotification extends Model
{
    public const UPDATED_AT = null;

    public const OUTREACH_FINALIZED = 'OUTREACH_FINALIZED';

    public const OUTREACH_RETURNED = 'OUTREACH_RETURNED';

    protected $table = 'notifications';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
