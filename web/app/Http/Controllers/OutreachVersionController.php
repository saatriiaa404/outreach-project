<?php

namespace App\Http\Controllers;

use App\Models\Outreach;
use App\Models\OutreachVersion;
use App\Models\OutreachVersionAttachment;
use App\Models\Signature;
use App\Models\StoredFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files of a historical version (attachments and signatures as submitted),
 * served only after checking full access to the Outreach.
 */
class OutreachVersionController extends Controller
{
    public function attachment(Outreach $outreach, OutreachVersion $version, OutreachVersionAttachment $attachment): StreamedResponse
    {
        $this->authorizeVersion($outreach, $version);
        abort_unless($attachment->outreach_version_id === $version->id, 404);

        return $this->stream($attachment->file, $attachment->file->mime_type);
    }

    public function signature(Outreach $outreach, OutreachVersion $version, string $form): StreamedResponse
    {
        $this->authorizeVersion($outreach, $version);
        $signatureId = match ($form) {
            'mustahik' => $version->mustahik_signature_id,
            'kifayah' => $version->kifayah_signature_id,
            default => abort(404),
        };
        $file = $signatureId ? Signature::query()->with('file')->find($signatureId)?->file : null;
        abort_if($file === null, 404);

        return $this->stream($file, 'image/png');
    }

    private function authorizeVersion(Outreach $outreach, OutreachVersion $version): void
    {
        Gate::authorize('view', $outreach);
        abort_unless($version->outreach_id === $outreach->id, 404);
    }

    private function stream(StoredFile $file, string $contentType): StreamedResponse
    {
        abort_if($file->purged_at !== null || ! Storage::disk($file->storage_disk)->exists($file->storage_key), 404);

        return Storage::disk($file->storage_disk)->response($file->storage_key, $file->original_name, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
