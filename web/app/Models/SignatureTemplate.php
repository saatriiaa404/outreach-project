<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A versioned Kepala signature template (Database v1.1 10.2). Templates are
 * never changed; a new signature is a new version, and approvals keep the
 * version they used. Managing them is roadmap R9.3.
 *
 * @property int $id
 * @property int $file_id
 * @property int $head_user_id
 * @property int $version_number
 * @property Carbon $valid_from
 * @property Carbon|null $valid_until
 * @property bool $is_active
 * @property-read StoredFile $file
 */
class SignatureTemplate extends Model
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
        // Only activation may change, so a replaced template can be retired.
        static::updating(function (SignatureTemplate $template) {
            if (array_diff(array_keys($template->getDirty()), ['is_active', 'valid_until']) !== []) {
                throw new LogicException('Signature templates are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Signature templates are immutable.'));
    }

    /**
     * The active template of a Kepala, if any.
     */
    public static function activeFor(User $kepala): ?self
    {
        return self::query()
            ->where('head_user_id', $kepala->id)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}
