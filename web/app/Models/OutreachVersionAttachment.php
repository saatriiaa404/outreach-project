<?php

namespace App\Models;

use App\Enums\AttachmentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Attachment metadata as submitted with a historical version (Database v1.1
 * 8.3); later changes to current attachments never touch it.
 *
 * @property int $id
 * @property int $outreach_version_id
 * @property int $file_id
 * @property AttachmentCategory $category
 * @property string|null $title
 * @property string|null $description
 * @property int $sort_order
 * @property-read StoredFile $file
 */
class OutreachVersionAttachment extends Model
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
            'category' => AttachmentCategory::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Historical attachments are immutable.'));
        static::deleting(fn () => throw new LogicException('Historical attachments are immutable.'));
    }

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}
