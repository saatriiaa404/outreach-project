<?php

namespace App\Actions\MasterData;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\SignatureTemplate;
use App\Models\StampTemplate;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Versioned Kepala signature and stamp templates managed by the ADMIN
 * (Database v1.1 10.2, 10.3; D-058). Templates are never edited or
 * deleted: an upload is a new version that becomes the active one, and the
 * previous active version is retired. Approvals keep the version they used.
 */
class ManageTemplates
{
    public const DISK = 'local';

    private const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    public function uploadSignature(User $admin, User $kepala, UploadedFile $upload): SignatureTemplate
    {
        if ($kepala->role !== UserRole::Kepala) {
            throw ValidationException::withMessages(['file' => 'Template tanda tangan hanya untuk akun Kepala.']);
        }

        return $this->store($upload, "templates/signatures/{$kepala->id}", function (StoredFile $file) use ($admin, $kepala) {
            // Locking the Kepala serializes uploads, so versions stay unique.
            User::query()->whereKey($kepala->id)->lockForUpdate()->first();
            $previous = SignatureTemplate::query()->where('head_user_id', $kepala->id)->where('is_active', true)->get();
            $previous->each(fn (SignatureTemplate $template) => $template->forceFill(['is_active' => false, 'valid_until' => now()])->save());

            $template = new SignatureTemplate;
            $template->forceFill([
                'file_id' => $file->id,
                'head_user_id' => $kepala->id,
                'version_number' => (int) SignatureTemplate::query()->where('head_user_id', $kepala->id)->max('version_number') + 1,
                'valid_from' => now(),
                'is_active' => true,
            ])->save();

            AuditLog::record($admin, 'SIGNATURE_TEMPLATE_ACTIVATED', $template, [
                'before_data' => ['active_template_ids' => $previous->pluck('id')->all()],
                'after_data' => ['template_id' => $template->id, 'version_number' => $template->version_number],
                'metadata' => ['head_user_id' => $kepala->id],
            ]);

            return $template;
        });
    }

    public function uploadStamp(User $admin, UploadedFile $upload): StampTemplate
    {
        return $this->store($upload, 'templates/stamps', function (StoredFile $file) use ($admin) {
            // Locking every row keeps at most one stamp active (10.3).
            $previous = StampTemplate::query()->lockForUpdate()->get()->where('is_active', true);
            $previous->each(fn (StampTemplate $stamp) => $stamp->forceFill(['is_active' => false, 'valid_until' => now()])->save());

            $stamp = new StampTemplate;
            $stamp->forceFill([
                'file_id' => $file->id,
                'version_number' => (int) StampTemplate::query()->max('version_number') + 1,
                'valid_from' => now(),
                'is_active' => true,
            ])->save();

            AuditLog::record($admin, 'STAMP_TEMPLATE_ACTIVATED', $stamp, [
                'before_data' => ['active_template_ids' => $previous->pluck('id')->values()->all()],
                'after_data' => ['template_id' => $stamp->id, 'version_number' => $stamp->version_number],
            ]);

            return $stamp;
        });
    }

    /**
     * Retires a version without a replacement; the Kepala then cannot
     * Approve until a new signature is uploaded (D-054).
     */
    public function deactivate(User $admin, SignatureTemplate|StampTemplate $template): void
    {
        DB::transaction(function () use ($admin, $template) {
            $locked = $template::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            if (! $locked->is_active) {
                return;
            }

            $locked->forceFill(['is_active' => false, 'valid_until' => now()])->save();

            AuditLog::record($admin, $locked instanceof SignatureTemplate ? 'SIGNATURE_TEMPLATE_DEACTIVATED' : 'STAMP_TEMPLATE_DEACTIVATED', $locked, [
                'before_data' => ['is_active' => true],
                'after_data' => ['is_active' => false],
            ]);
        });
    }

    /**
     * Checks the content, not the name or the client's type, then stores the
     * original privately and registers it before running $register.
     *
     * @template T
     *
     * @param  callable(StoredFile): T  $register
     * @return T
     */
    private function store(UploadedFile $upload, string $folder, callable $register): mixed
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($upload->getRealPath());
        $size = @getimagesize($upload->getRealPath());

        if (! isset(self::EXTENSIONS[$mime]) || $size === false) {
            throw ValidationException::withMessages(['file' => 'Berkas harus berupa gambar PNG atau JPG yang valid.']);
        }

        $key = "{$folder}/".Str::uuid().'.'.self::EXTENSIONS[$mime];

        if (! Storage::disk(self::DISK)->putFileAs(dirname($key), $upload, basename($key))) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak dapat disimpan. Coba lagi.']);
        }

        try {
            return DB::transaction(function () use ($upload, $key, $mime, $size, $register) {
                $file = new StoredFile;
                $file->forceFill([
                    'storage_disk' => self::DISK,
                    'storage_key' => $key,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => $mime,
                    'extension' => self::EXTENSIONS[$mime],
                    'size_bytes' => $upload->getSize(),
                    'sha256_hash' => hash_file('sha256', $upload->getRealPath()),
                    'width' => $size[0],
                    'height' => $size[1],
                ])->save();

                return $register($file);
            });
        } catch (Throwable $e) {
            // Nothing was registered, so the stored copy must not stay behind.
            Storage::disk(self::DISK)->delete($key);

            throw $e;
        }
    }
}
