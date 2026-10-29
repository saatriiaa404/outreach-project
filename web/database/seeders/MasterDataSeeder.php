<?php

namespace Database\Seeders;

use App\Models\DocumentTemplateVersion;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use Illuminate\Database\Seeder;

/**
 * Baseline Source and Type master data (Database v1.1 sections 4.2 and 4.3).
 * Codes are immutable (D-040), so existing rows are matched by code.
 */
class MasterDataSeeder extends Seeder
{
    public const SOURCES = [
        'BAZNAS' => 'Baznas',
        'CSR' => 'CSR',
        'BANGGA_SURABAYA' => 'Bangga Surabaya',
    ];

    public const TYPES = [
        'RUTILAHU' => 'Rutilahu',
        'ALAT_KESEHATAN' => 'Alat Kesehatan',
        'BEASISWA' => 'Beasiswa',
        'PENDIDIKAN_KEJAR_PAKET' => 'Pendidikan (Kejar Paket)',
        'BANTUAN_MODAL_USAHA' => 'Bantuan Modal Usaha',
    ];

    /**
     * Documents in each template package (Workflow v2.1: three PDFs for
     * Baznas/CSR, only the Outreach PDF for Bangga Surabaya).
     */
    public const TEMPLATE_PACKAGES = [
        'PACKAGE_BAZNAS_CSR' => ['outreach', 'mustahik', 'kifayah'],
        'PACKAGE_BANGGA_SURABAYA' => ['outreach'],
    ];

    public function run(): void
    {
        foreach (self::SOURCES as $code => $name) {
            OutreachSource::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true]);
        }

        foreach (self::TYPES as $code => $name) {
            OutreachType::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true]);
        }

        // One template package per Source (Q-09). Version 2 adds the logos, the
        // rank, the stamp and the ticked Kifayah boxes (D-064, D-065); versions
        // already finalized keep rendering with version 1.
        foreach (self::TEMPLATE_PACKAGES as $key => $documents) {
            foreach ([1, 2] as $number) {
                $template = DocumentTemplateVersion::query()->firstOrNew(['template_key' => $key, 'version_number' => $number]);

                if (! $template->exists) {
                    $template->forceFill([
                        'renderer_key' => "outreach-package-v{$number}",
                        'metadata' => array_fill_keys($documents, $number),
                        'is_active' => true,
                    ]);
                }

                $template->forceFill(['is_active' => $number === 2])->save();
            }
        }
    }
}
