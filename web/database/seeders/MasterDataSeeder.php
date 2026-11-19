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
    public const CURRENT_TEMPLATE = 5;

    /**
     * Template version of each document per package version; a document
     * that did not change keeps its previous template (v4 changes only
     * Mustahik and Kifayah, D-074).
     */
    public const TEMPLATE_VERSIONS = [
        1 => ['outreach' => 1, 'mustahik' => 1, 'kifayah' => 1],
        2 => ['outreach' => 2, 'mustahik' => 2, 'kifayah' => 2],
        3 => ['outreach' => 3, 'mustahik' => 3, 'kifayah' => 3],
        4 => ['outreach' => 3, 'mustahik' => 4, 'kifayah' => 4],
        5 => ['outreach' => 5, 'mustahik' => 5, 'kifayah' => 5],
    ];

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
        // rank, the stamp and the ticked Kifayah boxes (D-064, D-065); version 3
        // the letterhead image and the geometry of the source forms (D-070);
        // version 4 A4 Mustahik, larger logos and the Kifayah check columns (D-074);
        // version 5 the Carlito font of the source forms (D-075).
        // Versions already finalized keep rendering with the one they recorded.
        foreach (self::TEMPLATE_PACKAGES as $key => $documents) {
            foreach (array_keys(self::TEMPLATE_VERSIONS) as $number) {
                $template = DocumentTemplateVersion::query()->firstOrNew(['template_key' => $key, 'version_number' => $number]);

                if (! $template->exists) {
                    $template->forceFill([
                        'renderer_key' => "outreach-package-v{$number}",
                        'metadata' => array_intersect_key(self::TEMPLATE_VERSIONS[$number], array_flip($documents)),
                        'is_active' => true,
                    ]);
                }

                $template->forceFill(['is_active' => $number === self::CURRENT_TEMPLATE])->save();
            }
        }
    }
}
