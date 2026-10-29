<?php

namespace App\Actions\MasterData;

use App\Enums\OutreachStatus;
use App\Models\ArchiveRecord;
use App\Models\AuditLog;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manual archive marks by the ADMIN (D-038, D-059): the approved version's
 * PDFs were downloaded and uploaded to E-Surat/E-Arsip by hand, and the
 * application keeps a reference. One mark per approved version; corrections
 * and verification are audited.
 */
class ManageArchive
{
    /**
     * Admin marks within one month of the Kepala approval (D-038).
     */
    public static function deadline(CarbonInterface $approvedAt): CarbonInterface
    {
        return $approvedAt->copy()->addMonthNoOverflow();
    }

    /**
     * @param  array{external_system: string, archive_reference: string|null, archived_at: string}  $data
     */
    public function mark(User $admin, Outreach $outreach, int $versionId, array $data): ArchiveRecord
    {
        return DB::transaction(function () use ($admin, $outreach, $versionId, $data) {
            $locked = Outreach::query()->whereKey($outreach->id)->lockForUpdate()->firstOrFail();
            $version = $locked->latestVersion()->first();

            if ($locked->status !== OutreachStatus::Selesai || $version?->id !== $versionId || $version->status !== OutreachVersion::APPROVED) {
                throw ValidationException::withMessages(['archive' => 'Hanya versi terbaru yang sudah disahkan yang dapat diarsipkan. Muat ulang halaman.']);
            }

            if (ArchiveRecord::query()->where('outreach_version_id', $version->id)->exists()) {
                throw ValidationException::withMessages(['archive' => 'Versi ini sudah ditandai diarsipkan.']);
            }

            $record = new ArchiveRecord;
            $record->forceFill([
                'outreach_id' => $locked->id,
                'outreach_version_id' => $version->id,
                ...$data,
            ])->save();

            AuditLog::record($admin, 'ARCHIVE_MARKED', $record, [
                'after_data' => $data,
                'metadata' => ['outreach_id' => $locked->id, 'version_number' => $version->version_number],
            ]);

            return $record;
        });
    }

    /**
     * @param  array{external_system: string, archive_reference: string|null, archived_at: string}  $data
     */
    public function correct(User $admin, ArchiveRecord $record, array $data): void
    {
        DB::transaction(function () use ($admin, $record, $data) {
            $locked = ArchiveRecord::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $before = $this->values($locked);

            $locked->forceFill($data)->save();

            if ($locked->wasChanged()) {
                AuditLog::record($admin, 'ARCHIVE_CORRECTED', $locked, [
                    'before_data' => $before,
                    'after_data' => $this->values($locked),
                ]);
            }
        });
    }

    public function verify(User $admin, ArchiveRecord $record): void
    {
        DB::transaction(function () use ($admin, $record) {
            $locked = ArchiveRecord::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            if ($locked->verified_at !== null) {
                return;
            }

            $locked->forceFill(['verified_at' => now()])->save();

            AuditLog::record($admin, 'ARCHIVE_VERIFIED', $locked, [
                'after_data' => ['verified_at' => $locked->verified_at?->toIso8601String()],
            ]);
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function values(ArchiveRecord $record): array
    {
        return [
            'external_system' => $record->external_system,
            'archive_reference' => $record->archive_reference,
            'archived_at' => $record->archived_at?->toDateString(),
        ];
    }
}
