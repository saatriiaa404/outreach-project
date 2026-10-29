<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\SaveKifayah;
use App\Http\Requests\Outreach\SaveKifayahRequest;
use App\Models\Outreach;
use App\Support\OutreachSteps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Saving the Kifayah step (kifayah.md, D-036, D-037). The page itself is
 * rendered by OutreachController::edit() as one step of the stepper.
 */
class KifayahController extends Controller
{
    public function update(SaveKifayahRequest $request, Outreach $outreach, SaveKifayah $save): RedirectResponse
    {
        $save->handle($outreach, $request->user(), $request->formData(), $request->integer('edit_version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft Kifayah tersimpan.']);

        $next = $request->boolean('next') ? OutreachSteps::nextAfter($outreach, 'kifayah') : null;

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => $next ?? 'kifayah']);
    }

    /**
     * Background save; a stale edit_version answers 409 (CONFLICT).
     */
    public function autosave(SaveKifayahRequest $request, Outreach $outreach, SaveKifayah $save): JsonResponse
    {
        try {
            $version = $save->handle($outreach, $request->user(), $request->formData(), $request->integer('edit_version'), autosave: true);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['edit_version' => $version, 'saved_at' => now()->toIso8601String()]);
    }
}
