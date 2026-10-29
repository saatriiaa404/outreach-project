<?php

namespace App\Http\Controllers;

use App\Actions\MasterData\ManageTemplates;
use App\Enums\UserRole;
use App\Models\SignatureTemplate;
use App\Models\StampTemplate;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kepala signature and stamp templates for the ADMIN (design.md, D-058).
 */
class TemplateController extends Controller
{
    /**
     * Upload limit for template images (D-058).
     */
    public const MAX_KILOBYTES = 2048;

    public function index(): Response
    {
        Gate::authorize('viewAny', SignatureTemplate::class);
        Gate::authorize('viewAny', StampTemplate::class);

        $signatures = SignatureTemplate::query()->orderByDesc('version_number')->get()->groupBy('head_user_id');

        return Inertia::render('templates/Index', [
            'kepala' => User::query()->where('role', UserRole::Kepala)->orderBy('name')->get()->map(fn (User $kepala) => [
                'id' => $kepala->id,
                'name' => $kepala->name,
                'active' => $kepala->isActive(),
                'has_nip' => filled($kepala->nip),
                'versions' => ($signatures[$kepala->id] ?? collect())->map(fn (SignatureTemplate $template) => [
                    'id' => $template->id,
                    'version' => $template->version_number,
                    'active' => $template->is_active,
                    'valid_from' => $template->valid_from->toIso8601String(),
                    'valid_until' => $template->valid_until?->toIso8601String(),
                    'url' => route('templates.signatures.image', $template),
                ])->values(),
            ]),
            'stamps' => StampTemplate::query()->orderByDesc('version_number')->get()->map(fn (StampTemplate $stamp) => [
                'id' => $stamp->id,
                'version' => $stamp->version_number,
                'active' => $stamp->is_active,
                'valid_from' => $stamp->valid_from->toIso8601String(),
                'valid_until' => $stamp->valid_until?->toIso8601String(),
                'url' => route('templates.stamps.image', $stamp),
            ]),
            'maxKilobytes' => self::MAX_KILOBYTES,
        ]);
    }

    public function storeSignature(Request $request, User $kepala, ManageTemplates $templates): RedirectResponse
    {
        Gate::authorize('create', SignatureTemplate::class);
        $this->validateUpload($request);

        $template = $templates->uploadSignature($request->user(), $kepala, $request->file('file'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tanda tangan {$kepala->name} versi {$template->version_number} aktif."]);

        return to_route('templates.index');
    }

    public function storeStamp(Request $request, ManageTemplates $templates): RedirectResponse
    {
        Gate::authorize('create', StampTemplate::class);
        $this->validateUpload($request);

        $stamp = $templates->uploadStamp($request->user(), $request->file('file'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Stempel versi {$stamp->version_number} aktif."]);

        return to_route('templates.index');
    }

    public function deactivateSignature(Request $request, SignatureTemplate $template, ManageTemplates $templates): RedirectResponse
    {
        Gate::authorize('update', $template);
        $templates->deactivate($request->user(), $template);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Template tanda tangan dinonaktifkan.']);

        return to_route('templates.index');
    }

    public function deactivateStamp(Request $request, StampTemplate $stamp, ManageTemplates $templates): RedirectResponse
    {
        Gate::authorize('update', $stamp);
        $templates->deactivate($request->user(), $stamp);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stempel dinonaktifkan.']);

        return to_route('templates.index');
    }

    public function signatureImage(SignatureTemplate $template): StreamedResponse
    {
        Gate::authorize('viewAny', SignatureTemplate::class);

        return $this->stream($template->file);
    }

    public function stampImage(StampTemplate $stamp): StreamedResponse
    {
        Gate::authorize('viewAny', StampTemplate::class);

        return $this->stream($stamp->file);
    }

    private function validateUpload(Request $request): void
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:'.self::MAX_KILOBYTES],
        ], [], ['file' => 'berkas']);
    }

    private function stream(StoredFile $file): StreamedResponse
    {
        abort_if($file->purged_at !== null || ! Storage::disk($file->storage_disk)->exists($file->storage_key), 404);

        return Storage::disk($file->storage_disk)->response($file->storage_key, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
