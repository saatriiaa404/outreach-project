<?php

namespace App\Http\Controllers;

use App\Actions\Outreach\AttachFileToOutreach;
use App\Enums\AttachmentCategory;
use App\Enums\HistoryEventType;
use App\Http\Requests\Outreach\StoreAttachmentRequest;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Current attachments of an Outreach (Workflow v2.1 section 15). Changes are
 * allowed while the Petugas may edit; files are only served through show().
 */
class OutreachAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Outreach $outreach, AttachFileToOutreach $attach): RedirectResponse
    {
        $category = AttachmentCategory::from($request->string('category')->toString());

        $attach->handle(
            $outreach,
            $request->user(),
            $request->file('file'),
            $category,
            $request->filled('title') ? $request->string('title')->toString() : null,
            $request->filled('replaces') ? $request->integer('replaces') : null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$category->label()} tersimpan."]);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']);
    }

    public function destroy(Request $request, Outreach $outreach, OutreachAttachment $attachment): RedirectResponse
    {
        Gate::authorize('update', $outreach);
        abort_unless($attachment->outreach_id === $outreach->id, 404);

        $attachment->delete();

        HistoryEvent::record($outreach, $request->user(), HistoryEventType::SectionSaved, [
            'description' => "Lampiran dihapus: {$attachment->category->label()}",
            'metadata' => ['step' => 'lampiran', 'attachment_id' => $attachment->id],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lampiran dihapus.']);

        return to_route('outreaches.edit', ['outreach' => $outreach, 'step' => 'lampiran']);
    }

    /**
     * Streams a current attachment after checking access to its Outreach.
     */
    public function show(Outreach $outreach, OutreachAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $outreach);
        abort_unless($attachment->outreach_id === $outreach->id, 404);

        $file = $attachment->file;
        abort_if($file->purged_at !== null || ! Storage::disk($file->storage_disk)->exists($file->storage_key), 404);

        return Storage::disk($file->storage_disk)->response($file->storage_key, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
