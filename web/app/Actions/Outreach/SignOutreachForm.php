<?php

namespace App\Actions\Outreach;

use App\Enums\HistoryEventType;
use App\Enums\SignerType;
use App\Models\HistoryEvent;
use App\Models\KifayahForm;
use App\Models\MustahikForm;
use App\Models\Outreach;
use App\Models\Signature;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\FormSignatures;
use GdImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stores a signature drawn on the signature pad for the Mustahik (Petugas) or
 * Kifayah (Warga) form (Workflow v2.1 sections 16.1, 16.2). A new signature
 * replaces the previous one; signatures are never reused across Outreach.
 */
class SignOutreachForm
{
    /**
     * Upper bound for the decoded PNG; a full-width pad at 3x density is far below it.
     */
    public const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_SIDE = 4000;

    public function handle(Outreach $outreach, User $actor, string $form, string $dataUrl, int $expectedVersion): Signature
    {
        $definition = FormSignatures::FORMS[$form];
        $signerName = $definition['signer'] === SignerType::Petugas ? $actor->name : (string) $outreach->nama;

        if ($signerName === '') {
            throw ValidationException::withMessages(['image' => 'Isi Nama Klien di langkah Data Klien sebelum Warga menandatangani.']);
        }

        [$png, $width, $height] = $this->cleanPng($dataUrl);
        $key = "signatures/{$outreach->id}/".Str::uuid().'.png';

        if (! Storage::disk(AttachFileToOutreach::DISK)->put($key, $png)) {
            throw ValidationException::withMessages(['image' => 'Tanda tangan gagal disimpan. Coba lagi.']);
        }

        try {
            return DB::transaction(function () use ($outreach, $actor, $form, $definition, $signerName, $png, $width, $height, $key, $expectedVersion) {
                $this->bumpVersion($outreach, $expectedVersion);
                FormSignatures::invalidate($outreach, $form);

                $file = new StoredFile;
                $file->forceFill([
                    'storage_disk' => AttachFileToOutreach::DISK,
                    'storage_key' => $key,
                    'original_name' => "tanda-tangan-{$form}.png",
                    'mime_type' => 'image/png',
                    'extension' => 'png',
                    'size_bytes' => strlen($png),
                    'sha256_hash' => hash('sha256', $png),
                    'width' => $width,
                    'height' => $height,
                ])->save();

                $signature = new Signature;
                $signature->forceFill([
                    'file_id' => $file->id,
                    'signer_type' => $definition['signer'],
                    'signer_user_id' => $definition['signer'] === SignerType::Petugas ? $actor->id : null,
                    'signer_name_snapshot' => $signerName,
                    'signed_at' => now(),
                ])->save();

                $record = $form === 'mustahik'
                    ? MustahikForm::query()->firstOrNew(['outreach_id' => $outreach->id])
                    : KifayahForm::query()->firstOrNew(['outreach_id' => $outreach->id]);

                if ($record instanceof MustahikForm) {
                    $record->petugas_id ??= $outreach->petugas_id;
                }

                $record->forceFill(['signature_id' => $signature->id])->save();

                HistoryEvent::record($outreach, $actor, HistoryEventType::SectionSaved, [
                    'description' => "{$definition['title']} dibuat ({$definition['document']}).",
                    'metadata' => ['step' => 'tanda_tangan', 'form' => $form, 'signature_id' => $signature->id],
                ]);

                return $signature;
            });
        } catch (Throwable $e) {
            Storage::disk(AttachFileToOutreach::DISK)->delete($key);

            throw $e;
        }
    }

    public function clear(Outreach $outreach, User $actor, string $form, int $expectedVersion): void
    {
        DB::transaction(function () use ($outreach, $actor, $form, $expectedVersion) {
            $this->bumpVersion($outreach, $expectedVersion);

            if (FormSignatures::invalidate($outreach, $form)) {
                HistoryEvent::record($outreach, $actor, HistoryEventType::SectionSaved, [
                    'description' => FormSignatures::FORMS[$form]['title'].' dihapus untuk diulang.',
                    'metadata' => ['step' => 'tanda_tangan', 'form' => $form],
                ]);
            }
        });
    }

    private function bumpVersion(Outreach $outreach, int $expectedVersion): void
    {
        $updated = Outreach::query()
            ->whereKey($outreach->id)
            ->where('edit_version', $expectedVersion)
            ->update(['edit_version' => DB::raw('edit_version + 1'), 'updated_at' => now()]);

        if ($updated === 0) {
            throw ValidationException::withMessages([
                'edit_version' => 'Data Outreach telah berubah di tab atau sesi lain. Muat ulang halaman, lalu ulangi perubahan Anda.',
            ]);
        }
    }

    /**
     * Decodes the pad's PNG data URL, rejects anything else or an empty pad,
     * and re-encodes it so only plain pixel data is stored.
     *
     * @return array{0: string, 1: int, 2: int} PNG bytes, width, height
     */
    private function cleanPng(string $dataUrl): array
    {
        $invalid = fn (string $message) => ValidationException::withMessages(['image' => $message]);

        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw $invalid('Tanda tangan tidak valid. Ulangi tanda tangan.');
        }

        $bytes = base64_decode(substr($dataUrl, 22), true);
        $info = $bytes === false || strlen($bytes) > self::MAX_BYTES ? false : @getimagesizefromstring($bytes);

        if ($info === false || $info[2] !== IMAGETYPE_PNG || $info[0] > self::MAX_SIDE || $info[1] > self::MAX_SIDE) {
            throw $invalid('Tanda tangan tidak valid. Ulangi tanda tangan.');
        }

        $image = @imagecreatefromstring((string) $bytes);

        if (! $image instanceof GdImage) {
            throw $invalid('Tanda tangan tidak valid. Ulangi tanda tangan.');
        }

        if ($this->isBlank($image)) {
            throw $invalid('Tanda tangan masih kosong.');
        }

        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 9);

        return [(string) ob_get_clean(), imagesx($image), imagesy($image)];
    }

    /**
     * True when no sampled pixel has any ink (the pad exports a transparent background).
     */
    private function isBlank(GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) < 120) {
                    return false;
                }
            }
        }

        return true;
    }
}
