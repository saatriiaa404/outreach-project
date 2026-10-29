<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A versioned Kelurahan stamp (Database v1.1 10.3), at most one active.
 * Printing it on PDFs is PENDING VALIDATION, so approvals do not use it yet
 * (D-054, D-058).
 *
 * @property int $id
 * @property int $file_id
 * @property int $version_number
 * @property Carbon $valid_from
 * @property Carbon|null $valid_until
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property-read StoredFile $file
 */
class StampTemplate extends Model
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
            'version_number' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (StampTemplate $template) {
            if (array_diff(array_keys($template->getDirty()), ['is_active', 'valid_until']) !== []) {
                throw new LogicException('Stamp templates are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Stamp templates are immutable.'));
    }

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}
