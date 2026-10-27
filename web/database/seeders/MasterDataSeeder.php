<?php

namespace Database\Seeders;

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

    public function run(): void
    {
        foreach (self::SOURCES as $code => $name) {
            OutreachSource::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true]);
        }

        foreach (self::TYPES as $code => $name) {
            OutreachType::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true]);
        }
    }
}
