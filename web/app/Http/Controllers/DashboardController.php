<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageArchive;
use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Enums\UserRole;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\OutreachVersion;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use App\Support\OutreachColumnFilters;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Minimum dashboard per role (PRD 4.9, D-039): the PETUGAS sees their own
 * Outreach per status, the KEPALA the approval queue and completed
 * Outreach, the ADMIN every status, Special Unlock requests, archive status
 * and monthly charts (D-068). Every count goes through the same visibility
 * as the list.
 */
class DashboardController extends Controller
{
    /**
     * Kelurahan Manukan Kulon local time; timestamps are stored in UTC.
     */
    private const TIMEZONE = 'Asia/Jakarta';

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
            'focus' => $this->focus($user, $filters = OutreachColumnFilters::from($request), $focusStatus = $this->focusStatus($request, $user)),
            // Column filters of the focus table (D-072).
            'filters' => [...$filters, 'status' => $focusStatus?->value],
            'filterOptions' => [
                'tanggal' => OutreachColumnFilters::DATE_RANGES,
                'types' => OutreachType::query()->orderBy('name')->get(['id', 'name']),
                'statuses' => array_map(fn (OutreachStatus $status) => $status->value, $this->focusStatuses($user)),
            ],
            'admin' => $user->role === UserRole::Admin ? $this->adminSummary() : null,
            'charts' => $user->role === UserRole::Admin ? $this->adminCharts($this->month($request)) : null,
        ]);
    }

    /**
     * Statuses the focus table holds for this role.
     *
     * @return list<OutreachStatus>
     */
    private function focusStatuses(User $user): array
    {
        return $user->role === UserRole::Petugas
            ? [OutreachStatus::PerluPerbaikan, OutreachStatus::Draft]
            : [OutreachStatus::MenungguPengesahan];
    }

    private function focusStatus(Request $request, User $user): ?OutreachStatus
    {
        $status = OutreachStatus::tryFrom($request->string('status')->toString());

        return in_array($status, $this->focusStatuses($user), true) ? $status : null;
    }

    /**
     * What needs this user's attention first, narrowed by the column filters.
     *
     * @param  array{tanggal: string|null, source: int|null, type: int|null}  $filters
     * @return list<array<string, mixed>>
     */
    private function focus(User $user, array $filters, ?OutreachStatus $status): array
    {
        $query = OutreachColumnFilters::apply(Outreach::query()->visibleTo($user), $filters)
            ->whereIn('status', $status ? [$status] : $this->focusStatuses($user))
            ->with('type:id,name');

        $query = match ($user->role) {
            // Work in progress, revisions first.
            UserRole::Petugas => $query->orderByRaw('status = ? desc', [OutreachStatus::PerluPerbaikan->value])
                ->latest('updated_at'),
            // The approval queue, oldest submission first.
            UserRole::Kepala, UserRole::Admin => $query
                ->orderBy(OutreachVersion::query()->select('finalized_at')->whereColumn('outreach_id', 'outreaches.id')->orderByDesc('version_number')->limit(1)),
        };

        return array_values($query->limit(10)->get()->map(fn (Outreach $outreach) => [
            'id' => $outreach->id,
            'code' => $outreach->outreach_number ?? $outreach->draft_code,
            'nama' => $outreach->nama,
            'type' => $outreach->type->name,
            'outreach_date' => $outreach->outreach_date->toDateString(),
            'status' => $outreach->status->value,
            'can_edit' => $user->can('update', $outreach),
        ])->all());
    }

    /**
     * The month picked with ?bulan=YYYY-MM, the current one otherwise.
     */
    private function month(Request $request): CarbonImmutable
    {
        $value = $request->string('bulan')->toString();

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1
            ? CarbonImmutable::parse($value.'-01')->startOfMonth()
            : now(self::TIMEZONE)->startOfMonth();
    }

    /**
     * Counted by the Outreach date the Petugas entered; approvals by the
     * moment the Kepala approved, in local time.
     *
     * @return array<string, mixed>
     */
    private function adminCharts(CarbonImmutable $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $trendStart = $start->copy()->subMonths(11);

        $inMonth = Outreach::query()->whereBetween('outreach_date', [$start->toDateString(), $end->toDateString()]);
        $perDay = (clone $inMonth)->selectRaw('day(outreach_date) as day, count(*) as total')->groupBy('day')->pluck('total', 'day');
        $perType = (clone $inMonth)->selectRaw('type_id, count(*) as total')->groupBy('type_id')->pluck('total', 'type_id');
        $perSource = (clone $inMonth)->selectRaw('source_id, count(*) as total')->groupBy('source_id')->pluck('total', 'source_id');

        $created = Outreach::query()
            ->whereBetween('outreach_date', [$trendStart->toDateString(), $end->toDateString()])
            ->selectRaw("date_format(outreach_date, '%Y-%m') as month, count(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');
        $approved = OutreachApproval::query()
            ->where('decision', OutreachApproval::APPROVED)
            ->whereBetween('decided_at', [$trendStart->copy()->shiftTimezone(self::TIMEZONE)->utc(), $end->copy()->endOfDay()->shiftTimezone(self::TIMEZONE)->utc()])
            ->pluck('decided_at')
            ->countBy(fn ($at) => CarbonImmutable::parse($at)->setTimezone(self::TIMEZONE)->format('Y-m'));

        $ranked = fn ($models, $totals) => $models->map(fn ($model) => ['label' => $model->name, 'total' => (int) ($totals[$model->id] ?? 0)])
            ->sortByDesc('total')->values()->all();

        return [
            'month' => $start->format('Y-m'),
            'month_label' => $start->translatedFormat('F Y'),
            'total' => (int) $perDay->sum(),
            'per_day' => collect(range(1, $start->daysInMonth))->map(fn (int $day) => ['day' => $day, 'total' => (int) ($perDay[$day] ?? 0)])->all(),
            'per_type' => $ranked(OutreachType::query()->orderBy('name')->get(), $perType),
            'per_source' => $ranked(OutreachSource::query()->orderBy('name')->get(), $perSource),
            'trend' => collect(range(0, 11))->map(function (int $offset) use ($trendStart, $created, $approved) {
                $month = $trendStart->copy()->addMonths($offset);

                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->translatedFormat('M y'),
                    'created' => (int) ($created[$month->format('Y-m')] ?? 0),
                    'approved' => (int) ($approved[$month->format('Y-m')] ?? 0),
                ];
            })->all(),
        ];
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
