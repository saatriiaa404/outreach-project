<?php

namespace App\Models;

use App\Enums\AttachmentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A current attachment of an Outreach (Database v1.1 section 8.2). Removing
 * it soft-deletes the relation; attaching identical content again restores
 * that relation instead of creating a duplicate.
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $file_id
 * @property AttachmentCategory $category
 * @property string|null $title
 * @property string|null $description
 * @property int $sort_order
 * @property-read StoredFile $file
 * @property-read Outreach $outreach
 */
class OutreachAttachment extends Model
{
    use SoftDeletes;

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

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    /**
     * @return BelongsTo<Outreach, $this>
     */
    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }
}
