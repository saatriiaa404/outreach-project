<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\SignOutreachForm;
use App\Models\Outreach;
use App\Models\Signature;
use App\Support\FormSignatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Petugas and Warga signatures of Baznas/CSR (Workflow v2.1 section 16).
 * Signature images are private and only served through show().
 */
class SignatureController extends Controller
{
    public function store(Request $request, Outreach $outreach, string $form, SignOutreachForm $sign): RedirectResponse
    {
        $this->authorizeChange($outreach);
        $request->validate([
            'image' => ['required', 'string', 'max:'.(int) ceil(SignOutreachForm::MAX_BYTES * 4 / 3) + 32],
            'edit_version' => ['required', 'integer', 'min:0'],
        ]);

        $sign->handle($outreach, $request->user(), $form, $request->string('image')->toString(), $request->integer('edit_version'));

        Inertia::flash('toast', ['type' => 'success', 'message' => FormSignatures::FORMS[$form]['title'].' tersimpan.']);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'tanda_tangan']);
    }

    public function destroy(Request $request, Outreach $outreach, string $form, SignOutreachForm $sign): RedirectResponse
    {
        $this->authorizeChange($outreach);
        $request->validate(['edit_version' => ['required', 'integer', 'min:0']]);

        $sign->clear($outreach, $request->user(), $form, $request->integer('edit_version'));

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'tanda_tangan']);
    }

    public function show(Outreach $outreach, string $form): StreamedResponse
    {
        Gate::authorize('view', $outreach);

        $signatureId = FormSignatures::form($outreach, $form)?->signature_id;
        $file = $signatureId ? Signature::query()->with('file')->find($signatureId)?->file : null;
        abort_if($file === null || $file->purged_at !== null || ! Storage::disk($file->storage_disk)->exists($file->storage_key), 404);

        return Storage::disk($file->storage_disk)->response($file->storage_key, $file->original_name, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizeChange(Outreach $outreach): void
    {
        Gate::authorize('sign', $outreach);
        abort_unless($outreach->source->usesMustahikKifayah(), 404);
    }
}
