<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * The Kepala decision on one historical version (Database v1.1 9.2). It is
 * immutable once recorded; RETURNED always carries a note and no Kepala
 * signature.
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $outreach_version_id
 * @property int $kepala_id
 * @property string $decision
 * @property string|null $note
 * @property int|null $head_signature_template_id
 * @property int|null $stamp_template_id
 * @property Carbon $decided_at
 * @property-read User $kepala
 */
class OutreachApproval extends Model
{
    public const UPDATED_AT = null;

    public const APPROVED = 'APPROVED';

    public const RETURNED = 'RETURNED';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Approval decisions are immutable.'));
        static::deleting(fn () => throw new LogicException('Approval decisions are immutable.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function kepala(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_id');
    }
}
