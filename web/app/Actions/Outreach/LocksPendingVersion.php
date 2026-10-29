<?php

namespace App\Actions\Outreach;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\OutreachVersion;
use Illuminate\Validation\ValidationException;

/**
 * A Kepala decision applies only to the latest submission that is still
 * pending (Workflow v2.1 23.1, 32.2). Call inside a transaction: the row
 * lock also stops a double submit from deciding twice.
 */
trait LocksPendingVersion
{
    /**
     * @return array{Outreach, OutreachVersion}
     */
    private function lockPending(Outreach $outreach, int $versionId, string $errorKey): array
    {
        $locked = Outreach::query()->whereKey($outreach->id)->lockForUpdate()->firstOrFail();
        $version = $locked->latestVersion()->first();

        if ($locked->status !== OutreachStatus::MenungguPengesahan
            || $version?->id !== $versionId
            || $version->status !== OutreachVersion::PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                $errorKey => 'Pengajuan ini sudah diputuskan atau diganti versi baru. Muat ulang halaman.',
            ]);
        }

        return [$locked, $version];
    }
}
