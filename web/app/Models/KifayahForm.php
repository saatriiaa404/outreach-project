<?php

namespace App\Models;

use App\Support\KifayahFormSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Had Kifayah form of a Baznas/CSR Outreach (Database v1.1 section 6.3).
 * Nominal and count columns stay NULL until the Petugas fills them.
 *
 * @property int $id
 * @property int $outreach_id
 * @property int|null $signature_id
 * @property-read Collection<int, KifayahAnswer> $answers
 */
class KifayahForm extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['outreach_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_map(fn () => 'integer', KifayahFormSchema::FIELDS);
    }

    /**
     * @return HasMany<KifayahAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(KifayahAnswer::class);
    }
}
