<?php

namespace App\Actions\Outreach;

use App\Enums\AttachmentCategory;
use App\Models\HistoryEvent;
use App\Models\Outreach;
use App\Models\OutreachAttachment;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\ImageOptimizer;
use finfo;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Upload pipeline for Outreach attachments (Workflow v2.1 section 15.3):
 * content validation, SHA-256 duplicate check, private storage, file
 * registry, then the attachment relation. Images are compressed to JPEG
 * (ImageOptimizer); the original upload is not kept. PDFs are stored as is.
 */
class AttachFileToOutreach
{
    public const DISK = 'local';

    /**
     * Active attachments per Outreach, the four baseline ones included (D-064).
     */
    public const MAX_ATTACHMENTS = 10;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private ImageOptimizer $optimizer) {}

    /**
     * @param  int|null  $replaces  attachment that the new file replaces
     */
    public function handle(Outreach $outreach, User $actor, UploadedFile $upload, AttachmentCategory $category, ?string $title = null, ?int $replaces = null): OutreachAttachment
    {
        [$mime, $width, $height] = $this->inspect($upload, $category);
        $hash = hash_file('sha256', $upload->getRealPath());
        $storedKey = null;

        // Compressed before the row lock so the lock stays short; the hash above
        // still identifies the original content (D-047).
        $optimized = $mime === 'application/pdf' ? null : $this->optimizer->optimize($upload->getRealPath(), $mime);
        $source = $optimized['path'] ?? $upload->getRealPath();
        $storedMime = $optimized ? 'image/jpeg' : $mime;
        [$width, $height] = $optimized ? [$optimized['width'], $optimized['height']] : [$width, $height];

        try {
            return DB::transaction(function () use ($outreach, $actor, $upload, $category, $title, $replaces, $source, $storedMime, $width, $height, $hash, &$storedKey) {
                // Serializes uploads to one Outreach so the duplicate check holds.
                Outreach::query()->whereKey($outreach->id)->lockForUpdate()->first();

                $replaced = $replaces === null ? null : $outreach->attachments()->findOrFail($replaces);

                if (! $replaced && $outreach->attachments()->count() >= self::MAX_ATTACHMENTS) {
                    throw ValidationException::withMessages([
                        'file' => 'Maksimal '.self::MAX_ATTACHMENTS.' lampiran per Outreach. Hapus atau ganti lampiran yang ada.',
                    ]);
                }
                $existing = OutreachAttachment::withTrashed()
                    ->where('outreach_id', $outreach->id)
                    ->whereHas('file', fn ($query) => $query->where('sha256_hash', $hash)->whereNull('purged_at'))
                    ->first();

                if ($existing && ! $existing->trashed()) {
                    throw ValidationException::withMessages([
                        'file' => "Berkas yang sama sudah diunggah sebagai {$existing->category->label()}.",
                    ]);
                }

                $attributes = [
                    'category' => $category,
                    'title' => $title,
                    'sort_order' => (int) $outreach->attachments()->where('category', $category)->max('sort_order') + 1,
                ];

                if ($existing) {
                    // Identical content again: reactivate the old relation (database.md 8.2).
                    $existing->restore();
                    $existing->forceFill($attributes)->save();
                    $attachment = $existing;
                } else {
                    $storedKey = "attachments/{$outreach->id}/".Str::uuid().'.'.self::EXTENSIONS[$storedMime];

                    if (! Storage::disk(self::DISK)->putFileAs(dirname($storedKey), new File($source), basename($storedKey))) {
                        throw new RuntimeException('The attachment could not be stored.');
                    }

                    $file = new StoredFile;
                    $file->forceFill([
                        'storage_disk' => self::DISK,
                        'storage_key' => $storedKey,
                        'original_name' => Str::limit($upload->getClientOriginalName(), 250, ''),
                        'mime_type' => $storedMime,
                        'extension' => self::EXTENSIONS[$storedMime],
                        'size_bytes' => (int) filesize($source),
                        'sha256_hash' => $hash,
                        'width' => $width,
                        'height' => $height,
                    ])->save();

                    $attachment = new OutreachAttachment;
                    $attachment->forceFill([...$attributes, 'outreach_id' => $outreach->id, 'file_id' => $file->id])->save();
                }

                $replaced?->delete();

                HistoryEvent::recordEdit($outreach, $actor, [
                    'description' => ($replaced ? 'Lampiran diganti: ' : 'Lampiran ditambahkan: ').$category->label(),
                    'metadata' => ['step' => 'lampiran', 'attachment_id' => $attachment->id, 'replaced_id' => $replaced?->id],
                ]);

                return $attachment;
            });
        } catch (Throwable $e) {
            // Nothing was registered, so the stored copy must not stay behind.
            if ($storedKey !== null) {
                Storage::disk(self::DISK)->delete($storedKey);
            }

            throw $e;
        } finally {
            if ($optimized !== null) {
                @unlink($optimized['path']);
            }
        }
    }

    /**
     * Detects the content type from the bytes and checks that images decode.
     *
     * @return array{0: string, 1: int|null, 2: int|null}
     */
    private function inspect(UploadedFile $upload, AttachmentCategory $category): array
    {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($upload->getRealPath());

        if (! in_array($mime, $category->allowedMimeTypes(), true)) {
            throw ValidationException::withMessages([
                'file' => $category->isBaseline()
                    ? "{$category->label()} harus berupa gambar JPG, PNG, atau WEBP."
                    : 'Lampiran lain harus berupa gambar JPG, PNG, WEBP, atau PDF.',
            ]);
        }

        if ($mime === 'application/pdf') {
            return [$mime, null, null];
        }

        $size = @getimagesize($upload->getRealPath());

        if ($size === false || $size['mime'] !== $mime) {
            throw ValidationException::withMessages(['file' => 'Gambar tidak dapat dibaca. Unggah ulang berkas yang utuh.']);
        }

        return [$mime, $size[0], $size[1]];
    }
}
