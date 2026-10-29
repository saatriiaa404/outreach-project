<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Enums\SignerType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\DocumentTemplateVersion;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\OutreachVersion;
use App\Models\OutreachVersionAttachment;
use App\Models\Signature;
use App\Models\User;
use App\Support\FormSignatures;
use App\Support\OutreachFormSchema;
use App\Support\OutreachSnapshot;
use App\Support\SpecialUnlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Finalisasi and Finalisasi Ulang by the owning Petugas (Workflow v2.1
 * sections 18.1, 18.2, 19): full validation, outreach_number on the first
 * submission, an immutable historical version with its attachments and
 * signatures, status MENUNGGU_PENGESAHAN, History, and a notification to
 * every active Kepala. All of it commits together or not at all (18.4).
 */
class FinalizeOutreach
{
    public function handle(Outreach $outreach, User $actor, int $expectedVersion): OutreachVersion
    {
        return $this->submit($outreach, $actor, $expectedVersion, false);
    }

    /**
     * Lock & Ajukan untuk Pengesahan by the Admin (Workflow v2.1 18.3, 25.6):
     * the same validation and version, from MENUNGGU_PENGESAHAN or SELESAI
     * while Special Unlock is open, recorded as SPECIAL_UNLOCK_SUBMITTED.
     */
    public function submitAfterSpecialUnlock(Outreach $outreach, User $admin, int $expectedVersion): OutreachVersion
    {
        return $this->submit($outreach, $admin, $expectedVersion, true);
    }

    private function submit(Outreach $outreach, User $actor, int $expectedVersion, bool $afterSpecialUnlock): OutreachVersion
    {
        return DB::transaction(function () use ($outreach, $actor, $expectedVersion, $afterSpecialUnlock) {
            // The row lock also stops a double submit from creating two versions.
            $locked = Outreach::query()->whereKey($outreach->id)->lockForUpdate()->firstOrFail();
            $session = $afterSpecialUnlock ? SpecialUnlock::session($locked) : null;

            if ($afterSpecialUnlock ? $session === null : ! $locked->status->isEditableByPetugas()) {
                throw ValidationException::withMessages(['finalize' => $afterSpecialUnlock
                    ? 'Special Unlock pada Outreach ini sudah ditutup.'
                    : 'Outreach ini sudah difinalisasi.']);
            }

            if ($locked->edit_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi Finalisasi.',
                ]);
            }

            $missing = OutreachFormSchema::missingForFinalization($locked);

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'finalize' => 'Belum dapat difinalisasi: '.array_sum(array_map('count', $missing)).' hal masih kurang. Periksa daftar kelengkapan.',
                ]);
            }

            $refinalized = $locked->status === OutreachStatus::PerluPerbaikan;
            $number = $locked->outreach_number ?? $this->nextNumber($locked);
            $versionNumber = (int) OutreachVersion::query()->where('outreach_id', $locked->id)->max('version_number') + 1;
            $finalizedAt = now();

            $locked->forceFill([
                'outreach_number' => $number,
                'status' => OutreachStatus::MenungguPengesahan,
                'edit_version' => $locked->edit_version + 1,
            ])->save();

            $version = new OutreachVersion;
            $version->forceFill([
                'outreach_id' => $locked->id,
                'version_number' => $versionNumber,
                'status' => OutreachVersion::PENDING_APPROVAL,
                'snapshot_data' => OutreachSnapshot::build($locked, $versionNumber, $finalizedAt),
                'document_template_version_id' => $this->template($locked)->id,
                'mustahik_signature_id' => $this->signature($locked, 'mustahik', SignerType::Petugas),
                'kifayah_signature_id' => $this->signature($locked, 'kifayah', SignerType::Warga),
                'created_by' => $actor->id,
                'finalized_at' => $finalizedAt,
            ])->save();

            $locked->attachments()->get()->each(function (OutreachAttachment $attachment) use ($version) {
                (new OutreachVersionAttachment)->forceFill([
                    'outreach_version_id' => $version->id,
                    'file_id' => $attachment->file_id,
                    'category' => $attachment->category,
                    'title' => $attachment->title,
                    'description' => $attachment->description,
                    'sort_order' => $attachment->sort_order,
                ])->save();
            });

            $event = match (true) {
                $afterSpecialUnlock => HistoryEventType::SpecialUnlockSubmitted,
                $refinalized => HistoryEventType::Refinalized,
                default => HistoryEventType::Finalized,
            };

            HistoryEvent::record($locked, $actor, $event, [
                'outreach_version_id' => $version->id,
                'description' => $afterSpecialUnlock
                    ? "Versi {$versionNumber} diajukan oleh Admin setelah Special Unlock."
                    : "Versi {$versionNumber} diajukan untuk pengesahan.",
                'reason' => $session?->reason,
            ]);

            User::query()
                ->where('role', UserRole::Kepala)
                ->where('status', UserStatus::Active)
                ->pluck('id')
                ->each(fn (int $kepalaId) => (new InAppNotification)->forceFill([
                    'user_id' => $kepalaId,
                    'type' => InAppNotification::OUTREACH_FINALIZED,
                    'title' => "Outreach {$number} menunggu pengesahan",
                    'message' => "{$locked->nama} ({$locked->type->name}) diajukan oleh {$actor->name}, versi {$versionNumber}.",
                    'outreach_id' => $locked->id,
                    'outreach_version_id' => $version->id,
                ])->save());

            return $version;
        });
    }

    /**
     * OUT-<year of the Outreach date>-<5-digit sequence> (Q-04). Locking the
     * year's highest number serializes concurrent first submissions.
     */
    private function nextNumber(Outreach $outreach): string
    {
        $prefix = 'OUT-'.$outreach->outreach_date->format('Y').'-';
        $last = Outreach::query()
            ->where('outreach_number', 'like', $prefix.'%')
            ->orderByDesc('outreach_number')
            ->lockForUpdate()
            ->value('outreach_number');

        return $prefix.str_pad((string) ((int) substr((string) $last, strlen($prefix)) + 1), 5, '0', STR_PAD_LEFT);
    }

    private function template(Outreach $outreach): DocumentTemplateVersion
    {
        $template = DocumentTemplateVersion::query()
            ->where('template_key', DocumentTemplateVersion::keyFor($outreach->source->code))
            ->where('is_active', true)
            ->orderByDesc('version_number')
            ->first();

        if ($template === null) {
            throw new RuntimeException('No active document template for this Source.');
        }

        return $template;
    }

    /**
     * The signature used for the form, checked against its signer type
     * (database.md 9.1: the foreign key alone is not enough).
     */
    private function signature(Outreach $outreach, string $form, SignerType $expected): ?int
    {
        if (! $outreach->source->usesMustahikKifayah()) {
            return null;
        }

        $signatureId = FormSignatures::form($outreach, $form)?->signature_id;
        $signature = $signatureId === null ? null : Signature::query()->find($signatureId);

        if ($signature?->signer_type !== $expected) {
            throw ValidationException::withMessages(['finalize' => FormSignatures::FORMS[$form]['title'].' tidak valid. Tanda tangani ulang.']);
        }

        return $signature->id;
    }
}
