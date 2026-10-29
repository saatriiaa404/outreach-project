<?php

namespace App\Http\Controllers;

use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Support\OutreachPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * The Mustahik and Kifayah forms as they are printed, shown in the browser
 * (D-069): from a historical version, or from the current Draft on Review.
 * They carry no approval block, so anyone who may open the Outreach may
 * see them.
 */
class FormPreviewController extends Controller
{
    public function version(Outreach $outreach, OutreachVersion $version, string $form, OutreachPdf $pdf): Response
    {
        Gate::authorize('view', $outreach);
        abort_unless($version->outreach_id === $outreach->id && in_array($form, $pdf->documents($version), true), 404);

        return $this->page($pdf->preview($version, $form));
    }

    public function draft(Outreach $outreach, string $form, OutreachPdf $pdf): Response
    {
        Gate::authorize('view', $outreach);
        abort_unless($outreach->source->usesMustahikKifayah(), 404);

        return $this->page($pdf->draftPreview($outreach, $form));
    }

    private function page(string $html): Response
    {
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
