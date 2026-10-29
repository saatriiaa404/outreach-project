<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\ApproveOutreach;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Approve from the Kepala review page (Workflow v2.1 section 23).
 */
class ApproveOutreachController extends Controller
{
    public function __invoke(Request $request, Outreach $outreach, ApproveOutreach $approve): RedirectResponse
    {
        Gate::authorize('approve', $outreach);
        $request->validate(['outreach_version_id' => ['required', 'integer']]);

        $approve->handle($outreach, $request->user(), $request->integer('outreach_version_id'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Outreach {$outreach->outreach_number} disahkan dan seluruh PDF wajib berhasil dibuat.",
        ]);

        return to_route('outreaches.show', $outreach);
    }
}
