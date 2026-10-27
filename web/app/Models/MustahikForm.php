<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Mustahik form of a Baznas/CSR Outreach (Database v1.1 section 6.1).
 *
 * @property int $id
 * @property int $outreach_id
 * @property int $petugas_id
 * @property Carbon|null $survey_date
 * @property string|null $kepala_keluarga_nama
 * @property string|null $kepala_keluarga_nik_no_kk
 * @property string|null $kepala_keluarga_alamat
 * @property string|null $kepala_keluarga_rt
 * @property string|null $kepala_keluarga_rw
 * @property int|null $signature_id
 * @property-read Collection<int, MustahikAnswer> $answers
 * @property-read User $petugas
 */
class MustahikForm extends Model
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
        return [
            'survey_date' => 'date',
        ];
    }

    /**
     * @return HasMany<MustahikAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(MustahikAnswer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
