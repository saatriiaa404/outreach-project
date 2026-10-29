<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageArchive;
use App\Enums\OutreachStatus;
use App\Models\ArchiveRecord;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manual archive marks for approved Outreach (design.md Arsip, D-038, D-059).
 */
class ArchiveController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ArchiveRecord::class);

        $filter = in_array($request->query('filter'), ['pending', 'archived', 'all'], true) ? $request->query('filter') : 'pending';

        $rows = Outreach::query()
            ->where('status', OutreachStatus::Selesai)
            ->with(['latestVersion', 'type:id,name'])
            ->get()
            ->map(function (Outreach $outreach) {
                $version = $outreach->latestVersion;
                $approval = $version ? OutreachApproval::query()->where('outreach_version_id', $version->id)->where('decision', OutreachApproval::APPROVED)->first() : null;
                $record = $version ? ArchiveRecord::query()->where('outreach_version_id', $version->id)->first() : null;

                return $approval === null ? null : [
                    'outreach_id' => $outreach->id,
                    'code' => $outreach->outreach_number,
                    'nama' => $outreach->nama,
                    'type' => $outreach->type->name,
                    'version_id' => $version->id,
                    'version_number' => $version->version_number,
                    'approved_at' => $approval->decided_at->toDateString(),
                    'deadline' => ManageArchive::deadline($approval->decided_at)->toDateString(),
                    'overdue' => $record === null && ManageArchive::deadline($approval->decided_at)->isPast(),
                    'record' => $record === null ? null : [
                        'id' => $record->id,
                        'external_system' => $record->external_system,
                        'archive_reference' => $record->archive_reference,
                        'archived_at' => $record->archived_at?->toDateString(),
                        'verified_at' => $record->verified_at?->toIso8601String(),
                    ],
                ];
            })
            ->filter()
            ->filter(fn (array $row) => match ($filter) {
                'pending' => $row['record'] === null,
                'archived' => $row['record'] !== null,
                default => true,
            })
            // Closest deadline first.
            ->sortBy('deadline')
            ->values();

        return Inertia::render('archive/Index', [
            'rows' => $rows,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request, Outreach $outreach, ManageArchive $archive): RedirectResponse
    {
        Gate::authorize('create', ArchiveRecord::class);
        $request->validate(['outreach_version_id' => ['required', 'integer']]);

        $archive->mark($request->user(), $outreach, $request->integer('outreach_version_id'), $this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Outreach {$outreach->outreach_number} ditandai sudah diarsipkan."]);

        return back();
    }

    public function update(Request $request, ArchiveRecord $record, ManageArchive $archive): RedirectResponse
    {
        Gate::authorize('update', $record);

        $archive->correct($request->user(), $record, $this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data arsip dikoreksi.']);

        return back();
    }

    public function verify(Request $request, ArchiveRecord $record, ManageArchive $archive): RedirectResponse
    {
        Gate::authorize('update', $record);

        $archive->verify($request->user(), $record);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Arsip ditandai terverifikasi.']);

        return back();
    }

    /**
     * @return array{external_system: string, archive_reference: string|null, archived_at: string}
     */
    private function validated(Request $request): array
    {
        $request->validate([
            'external_system' => ['required', 'string', 'max:100'],
            'archive_reference' => ['nullable', 'string', 'max:255'],
            'archived_at' => ['required', 'date', 'before_or_equal:today'],
        ], [], [
            'external_system' => 'sistem arsip',
            'archive_reference' => 'nomor referensi',
            'archived_at' => 'tanggal arsip',
        ]);

        return [
            'external_system' => $request->string('external_system')->toString(),
            'archive_reference' => $request->filled('archive_reference') ? $request->string('archive_reference')->toString() : null,
            'archived_at' => $request->string('archived_at')->toString(),
        ];
    }
}
