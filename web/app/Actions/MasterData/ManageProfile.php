<?php

namespace App\Actions\MasterData;

use App\Models\AuditLog;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The optional account profile (D-067): photo, phone number and home address,
 * changed by the account owner or the ADMIN. The full name stays with the
 * ADMIN's account management because it is printed on documents. A replaced
 * photo is removed from storage; it is not part of any document.
 */
class ManageProfile
{
    public const DISK = 'local';

    private const MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public function __construct(private ImageOptimizer $optimizer) {}

    /**
     * @param  array{phone: string|null, address: string|null}  $data
     */
    public function update(User $actor, User $account, array $data, ?UploadedFile $photo, bool $removePhoto): void
    {
        $stored = $photo ? $this->storePhoto($account, $photo) : null;

        try {
            $previous = DB::transaction(function () use ($actor, $account, $data, $stored, $removePhoto) {
                $locked = User::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
                $before = $this->values($locked);
                $previous = ($stored || $removePhoto) ? $locked->photo_file_id : null;

                $locked->forceFill([
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'photo_file_id' => $stored ? $stored->id : ($removePhoto ? null : $locked->photo_file_id),
                ])->save();

                if ($locked->wasChanged()) {
                    AuditLog::record($actor, 'USER_PROFILE_UPDATED', $locked, [
                        'before_data' => $before,
                        'after_data' => $this->values($locked),
                    ]);
                }

                return $previous ? StoredFile::query()->find($previous) : null;
            });
        } catch (Throwable $e) {
            if ($stored) {
                Storage::disk(self::DISK)->delete($stored->storage_key);
                $stored->delete();
            }

            throw $e;
        }

        if ($previous) {
            Storage::disk($previous->storage_disk)->delete($previous->storage_key);
            $previous->forceFill(['purged_at' => now()])->save();
        }
    }

    /**
     * Checks the content, compresses it like an attachment photo and registers it.
     */
    private function storePhoto(User $account, UploadedFile $upload): StoredFile
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($upload->getRealPath());

        if (! in_array($mime, self::MIME_TYPES, true) || @getimagesize($upload->getRealPath()) === false) {
            throw ValidationException::withMessages(['photo' => 'Foto harus berupa gambar PNG, JPG, atau WEBP yang valid.']);
        }

        try {
            $optimized = $this->optimizer->optimize($upload->getRealPath(), $mime);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['photo' => $e->errors()['file'] ?? $e->getMessage()]);
        }

        $key = "profile-photos/{$account->id}/".Str::uuid().'.jpg';

        try {
            $contents = (string) file_get_contents($optimized['path']);

            if (! Storage::disk(self::DISK)->put($key, $contents)) {
                throw ValidationException::withMessages(['photo' => 'Foto tidak dapat disimpan. Coba lagi.']);
            }

            $file = new StoredFile;
            $file->forceFill([
                'storage_disk' => self::DISK,
                'storage_key' => $key,
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'size_bytes' => strlen($contents),
                'sha256_hash' => hash_file('sha256', $upload->getRealPath()),
                'width' => $optimized['width'],
                'height' => $optimized['height'],
            ])->save();

            return $file;
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($key);

            throw $e;
        } finally {
            @unlink($optimized['path']);
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function values(User $user): array
    {
        return [
            'phone' => $user->phone,
            'address' => $user->address,
            'photo_file_id' => $user->photo_file_id,
        ];
    }
}
