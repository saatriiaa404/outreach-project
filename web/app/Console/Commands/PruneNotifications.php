<?php

namespace App\Console\Commands;

use App\Models\InAppNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes in-app notifications older than their 30-day lifetime (Workflow
 * v2.1 30.4). Only notification rows go; Outreach, History, Audit,
 * approvals, versions, and files are untouched.
 */
#[Signature('notifications:prune')]
#[Description('Hapus notifikasi in-app yang berumur lebih dari 30 hari')]
class PruneNotifications extends Command
{
    public function handle(): int
    {
        $deleted = InAppNotification::query()
            ->where('created_at', '<', now()->subDays(InAppNotification::RETENTION_DAYS))
            ->delete();

        $this->info("{$deleted} notifikasi kedaluwarsa dihapus.");

        return self::SUCCESS;
    }
}
