<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Return by the Kepala (Workflow v2.1 section 21): a RETURNED decision with
 * its mandatory note on the latest pending version, status PERLU_PERBAIKAN,
 * History, and OUTREACH_RETURNED to the owning Petugas. The version itself
 * stays as submitted; only its status follows the decision.
 */
class ReturnOutreach
{
    use LocksPendingVersion;

    public function handle(Outreach $outreach, User $kepala, int $versionId, string $note): OutreachApproval
    {
        return DB::transaction(function () use ($outreach, $kepala, $versionId, $note) {
            [$locked, $version] = $this->lockPending($outreach, $versionId, 'return');

            $approval = new OutreachApproval;
            $approval->forceFill([
                'outreach_id' => $locked->id,
                'outreach_version_id' => $version->id,
                'kepala_id' => $kepala->id,
                'decision' => OutreachApproval::RETURNED,
                'note' => $note,
                'decided_at' => now(),
            ])->save();

            $version->forceFill(['status' => OutreachVersion::RETURNED])->save();

            $locked->forceFill([
                'status' => OutreachStatus::PerluPerbaikan,
                'edit_version' => $locked->edit_version + 1,
            ])->save();

            HistoryEvent::record($locked, $kepala, HistoryEventType::Returned, [
                'outreach_version_id' => $version->id,
                'approval_id' => $approval->id,
                'description' => "Versi {$version->version_number} dikembalikan untuk perbaikan.",
                'reason' => $note,
            ]);

            (new InAppNotification)->forceFill([
                'user_id' => $locked->petugas_id,
                'type' => InAppNotification::OUTREACH_RETURNED,
                'title' => "Outreach {$locked->outreach_number} dikembalikan",
                'message' => "Versi {$version->version_number} perlu diperbaiki. Catatan Kepala: {$note}",
                'outreach_id' => $locked->id,
                'outreach_version_id' => $version->id,
                'approval_id' => $approval->id,
            ])->save();

            return $approval;
        });
    }
}
