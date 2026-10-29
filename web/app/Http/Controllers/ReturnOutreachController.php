<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\ReturnOutreach;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Return from the Kepala review page (Workflow v2.1 section 21).
 */
class ReturnOutreachController extends Controller
{
    public function __invoke(Request $request, Outreach $outreach, ReturnOutreach $return): RedirectResponse
    {
        Gate::authorize('return', $outreach);
        // The note is mandatory (21.2); whitespace alone is trimmed to empty.
        $request->validate([
            'outreach_version_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:15000'],
        ], [], ['note' => 'catatan Return']);

        $return->handle($outreach, $request->user(), $request->integer('outreach_version_id'), $request->string('note')->toString());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Outreach {$outreach->outreach_number} dikembalikan kepada Petugas untuk perbaikan.",
        ]);

        return to_route('outreaches.index');
    }
}
