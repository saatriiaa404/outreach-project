<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\OutreachStatus;
use App\Models\HistoryEvent;
use App\Models\InAppNotification;
use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Models\SignatureTemplate;
use App\Models\StampTemplate;
use App\Models\User;
use App\Support\OutreachPdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Approve by the Kepala with the PDF gate (Workflow v2.1 sections 23, 26.3):
 * the decision is written, every required PDF of the Source is rendered from
 * the version with its approval block, and only if all of them succeed does
 * the transaction commit with status SELESAI. A failed PDF rolls everything
 * back, so the status stays MENUNGGU_PENGESAHAN and OUTREACH_COMPLETED is not
 * sent. PDFs are not stored; they are rendered again on demand (26.4).
 */
class ApproveOutreach
{
    use LocksPendingVersion;

    public function __construct(private OutreachPdf $pdf) {}

    public function handle(Outreach $outreach, User $kepala, int $versionId): OutreachApproval
    {
        try {
            $approval = DB::transaction(function () use ($outreach, $kepala, $versionId) {
                [$locked, $version] = $this->lockPending($outreach, $versionId, 'approve');

                // The approval block prints the NIP (D-041) and the signature template.
                if (blank($kepala->nip)) {
                    throw ValidationException::withMessages(['approve' => 'NIP Kepala belum diisi. Minta Admin melengkapi NIP sebelum mengesahkan.']);
                }

                $template = SignatureTemplate::activeFor($kepala);

                if ($template === null) {
                    throw ValidationException::withMessages(['approve' => 'Template tanda tangan Kepala belum tersedia. Minta Admin menyiapkannya sebelum mengesahkan.']);
                }

                $approval = new OutreachApproval;
                $approval->forceFill([
                    'outreach_id' => $locked->id,
                    'outreach_version_id' => $version->id,
                    'kepala_id' => $kepala->id,
                    'decision' => OutreachApproval::APPROVED,
                    'head_signature_template_id' => $template->id,
                    // The Kelurahan allows the digital stamp on the PDF (D-065).
                    'stamp_template_id' => StampTemplate::current()?->id,
                    'decided_at' => now(),
                ])->save();

                $version->forceFill(['status' => OutreachVersion::APPROVED])->save();

                $locked->forceFill([
                    'status' => OutreachStatus::Selesai,
                    'edit_version' => $locked->edit_version + 1,
                ])->save();

                HistoryEvent::record($locked, $kepala, HistoryEventType::Approved, [
                    'outreach_version_id' => $version->id,
                    'approval_id' => $approval->id,
                    'description' => "Versi {$version->version_number} disahkan.",
                    // The rank printed with this approval, kept after a promotion (D-065).
                    'metadata' => ['kepala_pangkat' => $kepala->pangkat],
                ]);

                $this->renderRequiredPdfs($version, $approval);

                return $approval;
            });
        } catch (PdfGateFailure $failure) {
            report($failure->getPrevious() ?? $failure);

            throw ValidationException::withMessages([
                'approve' => "Pengesahan gagal: {$failure->getMessage()} Status tetap Menunggu Pengesahan; coba lagi setelah masalah diperbaiki.",
            ]);
        }

        $this->notifyCompleted($approval);

        return $approval;
    }

    /**
     * The whole required package must render, not only one document (26.3).
     */
    private function renderRequiredPdfs(OutreachVersion $version, OutreachApproval $approval): void
    {
        $documents = $this->pdf->documents($version);

        if ($documents === []) {
            throw new PdfGateFailure('Paket template PDF untuk versi ini tidak ditemukan.');
        }

        $block = null;

        foreach ($documents as $document) {
            try {
                $block ??= $this->pdf->approvalBlock($approval);

                if (! str_starts_with($this->pdf->render($version, $document, $document === 'outreach' ? $block : null), '%PDF-')) {
                    throw new RuntimeException('Renderer did not return a PDF.');
                }
            } catch (Throwable $e) {
                throw new PdfGateFailure('PDF '.ucfirst($document).' tidak dapat dibuat.', 0, $e);
            }
        }
    }

    /**
     * Notifications are processed apart from the decision; a failure here
     * does not undo the approval (database.md 12.1).
     */
    private function notifyCompleted(OutreachApproval $approval): void
    {
        try {
            $outreach = Outreach::query()->findOrFail($approval->outreach_id);
            $version = OutreachVersion::query()->findOrFail($approval->outreach_version_id);

            (new InAppNotification)->forceFill([
                'user_id' => $outreach->petugas_id,
                'type' => InAppNotification::OUTREACH_COMPLETED,
                'title' => "Outreach {$outreach->outreach_number} disahkan",
                'message' => "Versi {$version->version_number} disahkan oleh {$approval->kepala->name}.",
                'outreach_id' => $outreach->id,
                'outreach_version_id' => $version->id,
                'approval_id' => $approval->id,
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
