<?php

namespace App\Support;

use App\Enums\OutreachStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Outreach search (PRD 4.9, D-039): name, NIK, internal ID, Source, Type,
 * and Outreach date range, always on top of the role's visibility. The
 * criteria live in the session, never in the path or query string, so a
 * NIK is not exposed in URLs (security.md, D-061).
 */
class OutreachSearch
{
    public const SESSION_KEY = 'outreach_search';

    public const FIELDS = ['nama', 'nik', 'code', 'source_id', 'type_id', 'date_from', 'date_to'];

    /**
     * @param  array<string, mixed>  $criteria
     * @param  Builder<Outreach>  $query
     * @return Builder<Outreach>
     */
    public static function apply(Builder $query, User $user, array $criteria): Builder
    {
        $nama = $criteria['nama'] ?? null;
        $nik = $criteria['nik'] ?? null;

        // A content match must not reveal what the Kepala may not see: only
        // status and Return notes of PERLU_PERBAIKAN (D-039).
        if (($nama || $nik) && $user->role === UserRole::Kepala) {
            $query->whereIn('status', [OutreachStatus::MenungguPengesahan, OutreachStatus::Selesai]);
        }

        return $query
            ->when($nama, fn (Builder $query, string $nama) => $query->where('nama', 'like', '%'.addcslashes($nama, '%_\\').'%'))
            // Exact NIK only, so the field cannot be used to enumerate residents.
            ->when($nik, fn (Builder $query, string $nik) => $query->where('nik', $nik))
            ->when($criteria['code'] ?? null, fn (Builder $query, string $code) => $query->where(fn (Builder $query) => $query
                ->where('outreach_number', 'like', '%'.addcslashes($code, '%_\\').'%')
                ->orWhere('draft_code', 'like', '%'.addcslashes($code, '%_\\').'%')))
            ->when($criteria['source_id'] ?? null, fn (Builder $query, int $id) => $query->where('source_id', $id))
            ->when($criteria['type_id'] ?? null, fn (Builder $query, int $id) => $query->where('type_id', $id))
            ->when($criteria['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('outreach_date', '>=', $date))
            ->when($criteria['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('outreach_date', '<=', $date));
    }
}
