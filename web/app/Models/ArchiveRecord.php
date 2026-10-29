<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The manual archive mark of an approved version (Database v1.1 13.1,
 * D-038). It is a reference to the external archive, not a PDF store.
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $outreach_version_id
 * @property string $external_system
 * @property string|null $archive_reference
 * @property Carbon|null $archived_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 */
class ArchiveRecord extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'verified_at' => 'datetime',
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
     * @return BelongsTo<OutreachVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(OutreachVersion::class, 'outreach_version_id');
    }
}
