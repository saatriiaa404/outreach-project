<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A file registered in the private storage registry (Database v1.1 section
 * 8.1). Named StoredFile to avoid clashing with the File facade.
 *
 * @property int $id
 * @property string $storage_disk
 * @property string $storage_key
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property string $sha256_hash hash of the original upload (D-047)
 * @property int|null $width
 * @property int|null $height
 * @property Carbon|null $purged_at
 */
class StoredFile extends Model
{
    protected $table = 'files';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'purged_at' => 'datetime',
        ];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
