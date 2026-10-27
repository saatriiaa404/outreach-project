<?php

namespace App\Models;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use Database\Factories\OutreachFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Form fields are added with the Outreach form (roadmap R2.2); this model
 * currently covers identity, ownership, and state needed for authorization.
 *
 * @property int $id
 * @property string $draft_code
 * @property string|null $outreach_number
 * @property int $source_id
 * @property int $type_id
 * @property Carbon $outreach_date
 * @property int $petugas_id
 * @property string|null $nama
 * @property OutreachStatus $status
 * @property int $edit_version
 */
class Outreach extends Model
{
    /** @use HasFactory<OutreachFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outreach_date' => 'date',
            'status' => OutreachStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * @return BelongsTo<OutreachSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(OutreachSource::class, 'source_id');
    }

    /**
     * @return BelongsTo<OutreachType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OutreachType::class, 'type_id');
    }

    /**
     * Limit a query to the Outreach records a user may see in lists, dashboards,
     * and search (roles-permissions.md, D-039). KEPALA rows for PERLU_PERBAIKAN
     * are listed but only their status and Return note may be shown.
     *
     * @param  Builder<Outreach>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if (! $user->isActive()) {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($user->role) {
            UserRole::Petugas => $query->where('petugas_id', $user->id),
            UserRole::Kepala => $query->where('status', '!=', OutreachStatus::Draft),
            UserRole::Admin => $query,
        };
    }
}
