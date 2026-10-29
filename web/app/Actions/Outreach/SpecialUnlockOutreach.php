<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Enums\SpecialUnlockRequestStatus;
use App\Models\AuditLog;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\SpecialUnlockRequest;
use App\Models\User;
use App\Support\SpecialUnlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Special Unlock by the Admin (Workflow v2.1 section 25): open a session
 * with a mandatory reason, then close it with Lock (back to the Petugas as
 * PERLU_PERBAIKAN, no new version, D-031) or Lock & Ajukan (a new version for
 * the Kepala). The status never takes a Special Unlock value; the session
 * lives in History (D-056). Each step locks the Outreach row, checks
 * edit_version, writes History and audit_logs, and moves the open request
 * along (D-055).
 */
class SpecialUnlockOutreach
{
    public function __construct(private FinalizeOutreach $finalize) {}

    public function unlock(Outreach $outreach, User $admin, string $reason, int $expectedVersion): void
    {
        DB::transaction(function () use ($outreach, $admin, $reason, $expectedVersion) {
            $locked = $this->lockRow($outreach, $expectedVersion);

            if (! $locked->status->allowsSpecialUnlock()) {
                throw ValidationException::withMessages(['special_unlock' => 'Special Unlock hanya untuk Outreach yang menunggu pengesahan atau sudah selesai.']);
            }

            if (SpecialUnlock::isActive($locked)) {
                throw ValidationException::withMessages(['special_unlock' => 'Special Unlock pada Outreach ini sudah aktif.']);
            }

            // Editors holding the old edit_version must reload.
            $locked->forceFill(['edit_version' => $locked->edit_version + 1])->save();
            $versionId = $locked->latestVersion()->value('id');

            HistoryEvent::record($locked, $admin, HistoryEventType::SpecialUnlocked, [
                'outreach_version_id' => $versionId,
                'description' => 'Special Unlock dibuka oleh Admin.',
                'reason' => $reason,
            ]);

            // A waiting request is now being handled and can no longer be cancelled.
            SpecialUnlockRequest::query()
                ->where('outreach_id', $locked->id)
                ->where('status', SpecialUnlockRequestStatus::Pending)
                ->update(['status' => SpecialUnlockRequestStatus::Processing, 'admin_id' => $admin->id, 'updated_at' => now()]);

            AuditLog::record($admin, 'SPECIAL_UNLOCK', $locked, [
                'metadata' => ['reason' => $reason, 'status' => $locked->status->value, 'outreach_version_id' => $versionId],
            ]);
        });
    }

    /**
     * The correction still needs the Petugas or the Warga (25.5).
     */
    public function lock(Outreach $outreach, User $admin, string $note, int $expectedVersion): void
    {
        DB::transaction(function () use ($outreach, $admin, $note, $expectedVersion) {
            $locked = $this->lockRow($outreach, $expectedVersion);
            $this->openSession($locked);
            $before = $locked->status;

            $locked->forceFill([
                'status' => OutreachStatus::PerluPerbaikan,
                'edit_version' => $locked->edit_version + 1,
            ])->save();

            $versionId = $locked->latestVersion()->value('id');

            HistoryEvent::record($locked, $admin, HistoryEventType::SpecialUnlockLocked, [
                'outreach_version_id' => $versionId,
                'description' => 'Dikunci kembali oleh Admin; dilanjutkan Petugas.',
                'reason' => $note,
            ]);

            $this->completeRequests($locked, $admin);

            AuditLog::record($admin, 'SPECIAL_UNLOCK_LOCK', $locked, [
                'before_data' => ['status' => $before->value],
                'after_data' => ['status' => OutreachStatus::PerluPerbaikan->value],
                'metadata' => ['note' => $note, 'outreach_version_id' => $versionId],
            ]);
        });
    }

    /**
     * The correction is complete and goes to the Kepala as a new version (25.6).
     */
    public function lockAndSubmit(Outreach $outreach, User $admin, int $expectedVersion): OutreachVersion
    {
        return DB::transaction(function () use ($outreach, $admin, $expectedVersion) {
            $before = $outreach->status;
            $version = $this->finalize->submitAfterSpecialUnlock($outreach, $admin, $expectedVersion);
            $locked = Outreach::query()->findOrFail($outreach->id);

            $this->completeRequests($locked, $admin);

            AuditLog::record($admin, 'SPECIAL_UNLOCK_SUBMIT', $locked, [
                'before_data' => ['status' => $before->value],
                'after_data' => ['status' => OutreachStatus::MenungguPengesahan->value],
                'metadata' => ['outreach_version_id' => $version->id, 'version_number' => $version->version_number],
            ]);

            return $version;
        });
    }

    private function lockRow(Outreach $outreach, int $expectedVersion): Outreach
    {
        $locked = Outreach::query()->whereKey($outreach->id)->lockForUpdate()->firstOrFail();

        if ($locked->edit_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi.',
            ]);
        }

        return $locked;
    }

    private function openSession(Outreach $locked): HistoryEvent
    {
        return SpecialUnlock::session($locked) ?? throw ValidationException::withMessages([
            'special_unlock' => 'Special Unlock pada Outreach ini sudah ditutup.',
        ]);
    }

    /**
     * A request being processed is fulfilled by the Lock (D-055).
     */
    private function completeRequests(Outreach $outreach, User $admin): void
    {
        SpecialUnlockRequest::query()
            ->where('outreach_id', $outreach->id)
            ->where('status', SpecialUnlockRequestStatus::Processing)
            ->update([
                'status' => SpecialUnlockRequestStatus::Completed,
                'admin_id' => $admin->id,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
