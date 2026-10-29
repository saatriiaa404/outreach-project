<?php

namespace App\Http\Controllers;

use App\Models\Outreach;
use App\Models\OutreachApproval;
use App\Models\OutreachVersion;
use App\Support\OutreachPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Final PDFs rendered on demand from an approved historical version
 * (Workflow v2.1 26.4). Nothing is stored; every download renders again
 * from the version, its attachments and signatures, and the approval it
 * received. PETUGAS never downloads (D-040).
 */
class OutreachPdfController extends Controller
{
    public const TITLES = [
        'outreach' => 'PDF Outreach (dengan lampiran)',
        'mustahik' => 'PDF Mustahik',
        'kifayah' => 'PDF Kifayah',
    ];

    public function __invoke(Outreach $outreach, OutreachVersion $version, string $document, OutreachPdf $pdf): Response|RedirectResponse
    {
        Gate::authorize('downloadFinalPdf', $outreach);
        abort_unless($version->outreach_id === $outreach->id && $version->status === OutreachVersion::APPROVED, 404);
        abort_unless(in_array($document, $pdf->documents($version), true), 404);

        $approval = OutreachApproval::query()
            ->where('outreach_version_id', $version->id)
            ->where('decision', OutreachApproval::APPROVED)
            ->with('kepala')
            ->firstOrFail();

        try {
            $content = $pdf->render($version, $document, $document === 'outreach' ? $pdf->approvalBlock($approval) : null);
        } catch (Throwable $e) {
            report($e);

            // Never hand out a partial document (26.4, 35.6).
            return to_route('outreaches.show', $outreach)->withErrors([
                'pdf' => self::TITLES[$document].' tidak dapat dibuat saat ini. Coba lagi; bila tetap gagal, hubungi Admin.',
            ]);
        }

        $name = "{$outreach->outreach_number}-v{$version->version_number}-{$document}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
