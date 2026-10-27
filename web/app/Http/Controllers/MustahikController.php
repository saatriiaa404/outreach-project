<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\SaveMustahik;
use App\Http\Requests\Outreach\SaveMustahikRequest;
use App\Models\Outreach;
use App\Support\OutreachSteps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Saving the Mustahik step (mustahik.md, D-036, D-048). The page itself is
 * rendered by OutreachController::edit() as one step of the stepper.
 */
class MustahikController extends Controller
{
    public function update(SaveMustahikRequest $request, Outreach $outreach, SaveMustahik $save): RedirectResponse
    {
        $save->handle($outreach, $request->user(), $request->formData(), $request->integer('edit_version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Draft Mustahik tersimpan.']);

        $next = $request->boolean('next') ? OutreachSteps::nextAfter($outreach, 'mustahik') : null;

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => $next ?? 'mustahik']);
    }

    /**
     * Background save; a stale edit_version answers 409 (CONFLICT).
     */
    public function autosave(SaveMustahikRequest $request, Outreach $outreach, SaveMustahik $save): JsonResponse
    {
        try {
            $version = $save->handle($outreach, $request->user(), $request->formData(), $request->integer('edit_version'), autosave: true);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['edit_version' => $version, 'saved_at' => now()->toIso8601String()]);
    }
}
