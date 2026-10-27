<?php

namespace App\Models;

use App\Enums\SignerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A signature drawn on the device (Database v1.1 section 10.1). The signer
 * name is a snapshot taken when signing.
 *
 * @property int $id
 * @property int $file_id
 * @property SignerType $signer_type
 * @property int|null $signer_user_id
 * @property string $signer_name_snapshot
 * @property Carbon $signed_at
 * @property-read StoredFile $file
 */
class Signature extends Model
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
            'signer_type' => SignerType::class,
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}
