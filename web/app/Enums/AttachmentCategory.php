<?php

namespace App\Enums;

/**
 * Outreach attachment categories (Database v1.1 section 8.2, D-015, D-034).
 */
enum AttachmentCategory: string
{
    case Ktp = 'KTP';
    case Kk = 'KK';
    case FotoKlien = 'FOTO_KLIEN';
    case FotoRumah = 'FOTO_RUMAH';
    case Tambahan = 'TAMBAHAN';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP',
            self::Kk => 'Kartu Keluarga (KK)',
            self::FotoKlien => 'Foto Klien',
            self::FotoRumah => 'Foto Rumah',
            self::Tambahan => 'Lampiran tambahan',
        };
    }

    /**
     * The four baseline attachments are required and must be images (D-034).
     */
    public function isBaseline(): bool
    {
        return $this !== self::Tambahan;
    }

    /**
     * Suggested additional attachment per Jenis Outreach (outreach.md 7.2).
     * Never required: PENDING VALIDATION until Kelurahan confirms (D-033).
     */
    public static function additionalFor(string $typeCode): ?string
    {
        return match ($typeCode) {
            'RUTILAHU' => 'Foto tambahan rumah; dapat lebih dari satu',
            'ALAT_KESEHATAN' => 'Foto orang yang membutuhkan alat kesehatan',
            'BEASISWA' => 'Foto penerima beasiswa',
            'PENDIDIKAN_KEJAR_PAKET' => 'Foto orang yang membutuhkan bantuan pendidikan',
            'BANTUAN_MODAL_USAHA' => 'Foto penerima bantuan',
            default => null,
        };
    }

    /**
     * Content types accepted for the category (D-047), detected from the
     * file content rather than the name or the client's Content-Type.
     *
     * @return list<string>
     */
    public function allowedMimeTypes(): array
    {
        $images = ['image/jpeg', 'image/png', 'image/webp'];

        return $this->isBaseline() ? $images : [...$images, 'application/pdf'];
    }
}
