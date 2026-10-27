<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\FinalizeOutreach;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Finalisasi from the Review step (Workflow v2.1 section 18).
 */
class FinalizeOutreachController extends Controller
{
    public function __invoke(Request $request, Outreach $outreach, FinalizeOutreach $finalize): RedirectResponse
    {
        Gate::authorize('finalize', $outreach);
        $request->validate(['edit_version' => ['required', 'integer', 'min:0']]);

        $version = $finalize->handle($outreach, $request->user(), $request->integer('edit_version'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Outreach {$outreach->refresh()->outreach_number} versi {$version->version_number} difinalisasi dan menunggu pengesahan Kepala.",
        ]);

        return to_route('outreaches.index');
    }
}
