<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageArchive;
use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Minimum dashboard per role (PRD 4.9, D-039): the PETUGAS sees their own
 * Outreach per status, the KEPALA the approval queue and completed
 * Outreach, the ADMIN every status, Special Unlock requests, and archive
 * status. Every count goes through the same visibility as the list.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $counts = Outreach::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = collect(OutreachStatus::cases())
            ->reject(fn (OutreachStatus $status) => match ($user->role) {
                UserRole::Kepala => ! in_array($status, [OutreachStatus::MenungguPengesahan, OutreachStatus::Selesai], true),
                default => false,
            })
            ->map(fn (OutreachStatus $status) => ['status' => $status->value, 'total' => (int) ($counts[$status->value] ?? 0)])
            ->values();

        return Inertia::render('Dashboard', [
            'role' => $user->role->value,
            'statuses' => $statuses,
            'focus' => $this->focus($user),
            'admin' => $user->role === UserRole::Admin ? $this->adminSummary() : null,
        ]);
    }

    /**
     * What needs this user's attention first.
     *
     * @return list<array<string, mixed>>
     */
    private function focus(User $user): array
    {
        $query = Outreach::query()->visibleTo($user)->with('type:id,name');

        $query = match ($user->role) {
            // Work in progress, revisions first.
            UserRole::Petugas => $query->whereIn('status', [OutreachStatus::PerluPerbaikan, OutreachStatus::Draft])
                ->orderByRaw('status = ? desc', [OutreachStatus::PerluPerbaikan->value])
                ->latest('updated_at'),
            // The approval queue, oldest submission first.
            UserRole::Kepala, UserRole::Admin => $query->where('status', OutreachStatus::MenungguPengesahan)
                ->orderBy(OutreachVersion::query()->select('finalized_at')->whereColumn('outreach_id', 'outreaches.id')->orderByDesc('version_number')->limit(1)),
        };

        return array_values($query->limit(5)->get()->map(fn (Outreach $outreach) => [
            'id' => $outreach->id,
            'code' => $outreach->outreach_number ?? $outreach->draft_code,
            'nama' => $outreach->nama,
            'type' => $outreach->type->name,
            'status' => $outreach->status->value,
            'can_edit' => $user->can('update', $outreach),
        ])->all());
    }

    /**
     * @return array{special_unlock_open: int, archive_pending: int, archive_overdue: int}
     */
    private function adminSummary(): array
    {
        $archive = collect(ManageArchive::rows())->where('record', null);

        return [
            'special_unlock_open' => SpecialUnlockRequest::query()->whereIn('status', SpecialUnlockRequestStatus::open())->count(),
            'archive_pending' => $archive->count(),
            'archive_overdue' => $archive->where('overdue', true)->count(),
        ];
    }
}
