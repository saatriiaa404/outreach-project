<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\SpecialUnlockOutreach;
use App\Models\Outreach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Special Unlock by the Admin (Workflow v2.1 section 25): open, then Lock or
 * Lock & Ajukan untuk Pengesahan. Editing in between uses the normal
 * stepper, authorized by OutreachPolicy::update while the session is open.
 */
class SpecialUnlockController extends Controller
{
    public function unlock(Request $request, Outreach $outreach, SpecialUnlockOutreach $specialUnlock): RedirectResponse
    {
        Gate::authorize('specialUnlock', $outreach);
        $request->validate([
            'reason' => ['required', 'string', 'max:15000'],
            'edit_version' => ['required', 'integer', 'min:0'],
        ], [], ['reason' => 'alasan Special Unlock']);

        $specialUnlock->unlock($outreach, $request->user(), $request->string('reason')->toString(), $request->integer('edit_version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Special Unlock dibuka. Perubahan dicatat dengan alasan ini sampai Lock atau Lock & Ajukan.']);

        return to_route('outreaches.edit', $outreach);
    }

    public function lock(Request $request, Outreach $outreach, SpecialUnlockOutreach $specialUnlock): RedirectResponse
    {
        Gate::authorize('lockSpecialUnlock', $outreach);
        // The Petugas reads this note to know what to continue (D-056).
        $request->validate([
            'note' => ['required', 'string', 'max:15000'],
            'edit_version' => ['required', 'integer', 'min:0'],
        ], [], ['note' => 'catatan untuk Petugas']);

        $specialUnlock->lock($outreach, $request->user(), $request->string('note')->toString(), $request->integer('edit_version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Outreach dikunci dan dikembalikan kepada Petugas sebagai Perlu Perbaikan.']);

        return to_route('outreaches.show', $outreach);
    }

    public function submit(Request $request, Outreach $outreach, SpecialUnlockOutreach $specialUnlock): RedirectResponse
    {
        Gate::authorize('lockSpecialUnlock', $outreach);
        $request->validate(['edit_version' => ['required', 'integer', 'min:0']]);

        $version = $specialUnlock->lockAndSubmit($outreach, $request->user(), $request->integer('edit_version'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Versi {$version->version_number} diajukan untuk pengesahan Kepala.",
        ]);

        return to_route('outreaches.show', $outreach);
    }
}
