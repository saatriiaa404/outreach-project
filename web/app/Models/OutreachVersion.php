<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A historical submission (Database v1.1 9.1). The snapshot and references
 * are immutable; only the version status follows the approval decision
 * (PENDING_APPROVAL, RETURNED, APPROVED).
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $version_number
 * @property string $status
 * @property array<string, mixed> $snapshot_data
 * @property int $document_template_version_id
 * @property int|null $mustahik_signature_id
 * @property int|null $kifayah_signature_id
 * @property int $created_by
 * @property Carbon $finalized_at
 * @property-read OutreachApproval|null $approval
 */
class OutreachVersion extends Model
{
    public const UPDATED_AT = null;

    public const PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const RETURNED = 'RETURNED';

    public const APPROVED = 'APPROVED';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'version_number' => 'integer',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (OutreachVersion $version) {
            if (array_diff(array_keys($version->getDirty()), ['status']) !== []) {
                throw new LogicException('Historical versions are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Historical versions are immutable.'));
    }

    /**
     * @return BelongsTo<Outreach, $this>
     */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }

    /**
     * @return BelongsTo<DocumentTemplateVersion, $this>
     */
    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'document_template_version_id');
    }

    /**
     * @return HasMany<OutreachVersionAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(OutreachVersionAttachment::class);
    }

    /**
     * @return HasOne<OutreachApproval, $this>
     */
    public function approval(): HasOne
    {
        return $this->hasOne(OutreachApproval::class);
    }
}
