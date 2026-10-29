<?php

namespace App\Support;

use App\Enums\AttachmentCategory;
use App\Models\Outreach;
use InvalidArgumentException;

/**
 * Field definitions of the Outreach stepper steps that hold form data
 * (Workflow v2.1 section 8.1, outreach.md section 4). Labels follow the blank
 * template; rules apply to Draft saves (types and ranges only). "required"
 * and "requiredIf" drive the Finalisasi completeness check, which also feeds
 * the step completion indicator. NULL means not filled and is never read as 0.
 *
 * Field shape: label, input (text|textarea|integer|money|date|select|boolean),
 * required (bool), optional options, requiredIf ([field, value]), group.
 */
class OutreachFormSchema
{
    /**
     * Status Kesejahteraan labels per desil from the official DTST division
     * used by the Kelurahan (D-019, D-064).
     */
    public const DESIL_LABELS = [
        1 => 'Sangat Miskin',
        2 => 'Miskin',
        3 => 'Kelompok Hampir Miskin / Rentan',
        4 => 'Kelompok Rentan Miskin / Menengah Bawah',
        5 => 'Kelompok Pas-pasan / Menengah Bawah Stabil',
        6 => 'Kelompok Menengah',
        7 => 'Kelompok Menengah Atas',
        8 => 'Kelompok Mapan / Menengah Atas',
        9 => 'Kelompok Sangat Mapan',
        10 => 'Kelompok Paling Sejahtera / Kaya',
    ];

    /**
     * Fasilitas MCK values (D-021), keyed by stored code.
     */
    public const MCK_OPTIONS = [
        'LAYAK' => 'Layak',
        'KURANG_LAYAK' => 'Kurang layak',
        'TIDAK_LAYAK' => 'Tidak layak',
        'TIDAK_ADA' => 'Tidak ada',
    ];

    /**
     * Largest accepted rupiah amount; keeps values well inside BIGINT UNSIGNED.
     */
    private const MAX_RUPIAH = 999_999_999_999;

    /**
     * Longest accepted narrative text; fits a MySQL TEXT column in utf8mb4.
     */
    private const MAX_TEXT = 15_000;

    /**
     * @return array<string, array{title: string, fields: array<string, array<string, mixed>>}>
     */
    public static function steps(): array
    {
        $money = fn (string $label, string $group) => ['label' => $label, 'input' => 'money', 'required' => true, 'group' => $group];
        $text = fn (string $label, bool $required = true) => ['label' => $label, 'input' => 'text', 'required' => $required];

        return [
            'identitas' => [
                'title' => 'Identitas Outreach',
                'fields' => [
                    'outreach_date' => ['label' => 'Tanggal Outreach', 'input' => 'date', 'required' => true],
                ],
            ],
            'klien' => [
                'title' => 'Data Klien',
                'fields' => [
                    'nama' => $text('Nama'),
                    'nik' => $text('NIK'),
                    // Template field 3 is entered as separate parts; age is derived (D-045).
                    'tempat_lahir' => $text('Tempat lahir'),
                    'tanggal_lahir' => ['label' => 'Tanggal lahir', 'input' => 'date', 'required' => true, 'showsAge' => true],
                    'agama' => $text('Agama'),
                    'alamat_ktp' => ['label' => 'Alamat KTP', 'input' => 'textarea', 'required' => true],
                    'alamat_domisili' => ['label' => 'Alamat Domisili', 'input' => 'textarea', 'required' => true],
                    'status_kependudukan' => ['label' => 'Status Kependudukan', 'input' => 'select', 'required' => true, 'options' => ['WNI' => 'WNI', 'WNA' => 'WNA']],
                    'tahun_warga_surabaya' => $text('Menjadi warga Surabaya sejak'),
                    'pekerjaan' => $text('Pekerjaan'),
                    'status_hubungan_keluarga' => $text('Status Hubungan dalam Keluarga'),
                    'jumlah_anggota_kk' => ['label' => 'Jumlah anggota keluarga dalam 1 KK', 'input' => 'integer', 'required' => true],
                    'jumlah_kk_dalam_rumah' => ['label' => 'Jumlah KK dalam 1 rumah', 'input' => 'integer', 'required' => true],
                    'status_tempat_tinggal' => $text('Status tempat tinggal'),
                    'penghasilan_klien' => ['label' => 'Penghasilan klien sebulan', 'input' => 'money', 'required' => false],
                    'no_telp' => $text('No. telp yang bisa dihubungi'),
                    'status_perkawinan' => $text('Status Perkawinan'),
                    'pendidikan_terakhir' => $text('Pendidikan terakhir', false),
                    'desil_1_10' => ['label' => 'Status Kesejahteraan', 'input' => 'select', 'required' => true, 'options' => self::desilOptions()],
                    'jaminan_kesehatan' => $text('Jaminan Kesehatan yang dimiliki'),
                    'merokok' => ['label' => 'Apakah Klien merokok', 'input' => 'boolean', 'required' => true],
                    'bantuan_pernah_diterima' => ['label' => 'Bantuan yang pernah diterima', 'input' => 'boolean', 'required' => true],
                    'bantuan_detail' => ['label' => 'Uraian bantuan yang pernah diterima', 'input' => 'textarea', 'required' => false, 'requiredIf' => ['bantuan_pernah_diterima', true]],
                    'punya_hutang' => ['label' => 'Apakah klien punya pinjaman / hutang', 'input' => 'boolean', 'required' => true],
                    'besar_hutang' => ['label' => 'Berapa besar pinjaman / hutang', 'input' => 'money', 'required' => false, 'requiredIf' => ['punya_hutang', true]],
                ],
            ],
            'keluarga' => [
                'title' => 'Keluarga',
                'fields' => [
                    'penghasilan_kepala_keluarga' => $money('Pendapatan Kepala Keluarga', 'pendapatan'),
                    'penghasilan_suami_istri' => $money('Pendapatan Suami / Istri', 'pendapatan'),
                    'penghasilan_anak_1kk' => $money('Pendapatan Anak yang masuk dalam satu KK', 'pendapatan'),
                    'penghasilan_lainnya' => $money('Pendapatan lainnya', 'pendapatan'),
                    'pengeluaran_sewa' => $money('Biaya sewa/kos/kontrak rumah', 'pengeluaran'),
                    'pengeluaran_belanja_memasak' => $money('Biaya belanja memasak', 'pengeluaran'),
                    'pengeluaran_air_minum' => $money('Biaya air minum / gallon', 'pengeluaran'),
                    'pengeluaran_lpg_pgn' => $money('Biaya LPG / PGN', 'pengeluaran'),
                    'pengeluaran_listrik' => $money('Biaya Listrik / PLN', 'pengeluaran'),
                    'pengeluaran_pdam' => $money('Biaya PDAM', 'pengeluaran'),
                    'pengeluaran_sekolah' => $money('Biaya sekolah', 'pengeluaran'),
                    'pengeluaran_internet' => $money('Biaya Internet / Wifi / Indihome', 'pengeluaran'),
                    'pengeluaran_pulsa' => $money('Biaya Pulsa HP', 'pengeluaran'),
                    'pengeluaran_rokok' => $money('Biaya Rokok', 'pengeluaran'),
                    'pengeluaran_berobat' => $money('Biaya berobat', 'pengeluaran'),
                    'pengeluaran_angsuran' => $money('Biaya / Angsuran pinjaman', 'pengeluaran'),
                    'pengeluaran_bbm' => $money('Biaya BBM / bensin', 'pengeluaran'),
                ],
            ],
            'rumah' => [
                'title' => 'Rumah & Aset',
                'fields' => [
                    'luas_bangunan' => $text('Ukuran / Luas bangunan rumah'),
                    'jenis_lantai' => $text('Jenis lantai'),
                    'jenis_dinding' => $text('Jenis dinding'),
                    'jenis_atap' => $text('Jenis atap bangunan'),
                    'daya_listrik' => $text('Daya listrik'),
                    'sumber_air' => $text('Sumber air'),
                    'mck_layak' => ['label' => 'Fasilitas MCK', 'input' => 'select', 'required' => true, 'options' => self::MCK_OPTIONS],
                    'jumlah_sepeda_angin' => ['label' => 'Sepeda angin', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'jumlah_sepeda_listrik' => ['label' => 'Sepeda listrik', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'jumlah_becak' => ['label' => 'Becak', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'jumlah_sepeda_motor' => ['label' => 'Sepeda motor', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'jumlah_roda_4' => ['label' => 'Roda 4', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'jumlah_kendaraan_lainnya' => ['label' => 'Kendaraan lainnya', 'input' => 'integer', 'required' => true, 'group' => 'kendaraan'],
                    'nama_kendaraan_lainnya' => ['label' => 'Nama kendaraan lainnya', 'input' => 'text', 'required' => false, 'group' => 'kendaraan', 'requiredIf' => ['jumlah_kendaraan_lainnya', '>0']],
                    'perhiasan' => ['label' => 'Perhiasan', 'input' => 'textarea', 'required' => false],
                    'aset_lainnya' => ['label' => 'Aset lainnya', 'input' => 'textarea', 'required' => false],
                ],
            ],
            'narasi' => [
                'title' => 'Narasi',
                'fields' => [
                    'upaya_dilakukan' => ['label' => 'Upaya yang telah dilakukan', 'input' => 'textarea', 'required' => true],
                    'harapan_klien' => ['label' => 'Harapan klien', 'input' => 'textarea', 'required' => true],
                    'uraian_alasan_intervensi' => ['label' => 'Uraian detail mengapa klien diusulkan intervensi ini', 'input' => 'textarea', 'required' => true],
                ],
            ],
        ];
    }

    /**
     * Steps that are not built yet in this roadmap phase, by Source code
     * (Workflow v2.1 sections 8.1, 9.1 to 9.3).
     *
     * @return array<string, string>
     */
    public static function upcomingSteps(string $sourceCode): array
    {
        $withForms = ['mustahik' => 'Mustahik', 'kifayah' => 'Kifayah', 'lampiran' => 'Lampiran', 'tanda_tangan' => 'Tanda Tangan', 'review' => 'Review'];

        return $sourceCode === 'BANGGA_SURABAYA'
            ? ['lampiran' => 'Lampiran', 'review' => 'Review']
            : $withForms;
    }

    /**
     * Draft-save validation rules for one step.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $step, ?Outreach $outreach = null): array
    {
        $rules = [];

        foreach (self::steps()[$step]['fields'] as $name => $field) {
            $rules[$name] = match ($field['input']) {
                'text' => ['nullable', 'string', 'max:'.self::maxLength($name)],
                'textarea' => ['nullable', 'string', 'max:'.self::MAX_TEXT],
                'integer' => ['nullable', 'integer', 'min:0', 'max:65535'],
                'money' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_RUPIAH],
                'date' => [$name === 'outreach_date' ? 'required' : 'nullable', 'date_format:Y-m-d'],
                'select' => ['nullable', 'in:'.implode(',', array_keys($field['options']))],
                'boolean' => ['nullable', 'boolean'],
                default => throw new InvalidArgumentException("Unknown input type for field [{$name}]."),
            };
        }

        if (isset($rules['tanggal_lahir']) && $outreach) {
            // A birth date after the Outreach date would give a negative age (D-045).
            $rules['tanggal_lahir'][] = 'before_or_equal:'.$outreach->outreach_date->toDateString();
        }

        return $rules;
    }

    /**
     * Completion state of a step for the stepper indicator: complete,
     * incomplete (some data but required fields missing), or untouched.
     */
    public static function completion(Outreach $outreach, string $step): string
    {
        $fields = self::steps()[$step]['fields'];

        if (collect(array_keys($fields))->every(fn (string $name) => self::isBlank($outreach->getAttribute($name)))) {
            return 'untouched';
        }

        return self::missingFields($outreach, $step) === [] ? 'complete' : 'incomplete';
    }

    /**
     * Labels of the fields of one step that Finalisasi still needs
     * (outreach.md section 4). Draft saves never require them.
     *
     * @return list<string>
     */
    public static function missingFields(Outreach $outreach, string $step): array
    {
        $missing = [];

        foreach (self::steps()[$step]['fields'] as $name => $field) {
            if (self::isRequired($outreach, $field) && self::isBlank($outreach->getAttribute($name))) {
                $missing[] = $field['label'];
            }
        }

        return $missing;
    }

    /**
     * Labels of the baseline attachments (KTP, KK, Foto Klien, Foto Rumah)
     * that are still missing as images (Workflow v2.1 15.2, D-034). Additional
     * attachments never block Finalisasi (D-033).
     *
     * @return list<string>
     */
    public static function missingAttachments(Outreach $outreach): array
    {
        $present = $outreach->attachments()
            ->whereHas('file', fn ($query) => $query->where('mime_type', 'like', 'image/%')->whereNull('purged_at'))
            ->distinct()
            ->pluck('category');

        $missing = [];

        foreach (AttachmentCategory::cases() as $category) {
            if ($category->isBaseline() && ! $present->contains($category)) {
                $missing[] = $category->label();
            }
        }

        return $missing;
    }

    /**
     * What Finalisasi still needs, per step: form fields, the Mustahik and
     * Kifayah forms and their signatures for Baznas/CSR, and the baseline
     * attachments.
     *
     * @return array<string, list<string>>
     */
    public static function missingForFinalization(Outreach $outreach): array
    {
        return collect(array_keys(self::steps()))
            ->mapWithKeys(fn (string $step) => [$step => self::missingFields($outreach, $step)])
            ->when($outreach->source->usesMustahikKifayah(), fn ($missing) => $missing
                ->put('mustahik', MustahikFormSchema::missing($outreach->mustahikForm))
                ->put('kifayah', KifayahFormSchema::missing($outreach->kifayahForm)))
            ->put('lampiran', self::missingAttachments($outreach))
            ->when($outreach->source->usesMustahikKifayah(), fn ($missing) => $missing->put('tanda_tangan', FormSignatures::missing($outreach)))
            ->filter()
            ->all();
    }

    /**
     * Sum of a derived total's components (outreach.md 4.2, 4.3), or null
     * while any component is still empty: an empty value is not zero.
     */
    public static function total(Outreach $outreach, string $group): ?int
    {
        $total = 0;

        foreach (self::steps()['keluarga']['fields'] as $name => $field) {
            if (($field['group'] ?? null) !== $group) {
                continue;
            }

            $value = $outreach->getAttribute($name);

            if ($value === null) {
                return null;
            }

            $total += (int) $value;
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function isRequired(Outreach $outreach, array $field): bool
    {
        if ($field['required']) {
            return true;
        }

        if (! isset($field['requiredIf'])) {
            return false;
        }

        [$controller, $expected] = $field['requiredIf'];
        $value = $outreach->getAttribute($controller);

        return $expected === '>0' ? (int) $value > 0 : $value === $expected;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private static function maxLength(string $name): int
    {
        return match ($name) {
            'nik' => 32,
            'tempat_lahir' => 100,
            'no_telp' => 30,
            'tahun_warga_surabaya' => 50,
            'agama', 'pekerjaan', 'status_hubungan_keluarga', 'status_tempat_tinggal', 'status_perkawinan', 'pendidikan_terakhir',
            'luas_bangunan', 'jenis_lantai', 'jenis_dinding', 'jenis_atap', 'daya_listrik', 'sumber_air' => 100,
            default => 255,
        };
    }

    /**
     * @return array<int, string>
     */
    private static function desilOptions(): array
    {
        $options = [];
        foreach (self::DESIL_LABELS as $desil => $label) {
            $options[$desil] = "Desil {$desil} — {$label}";
        }

        return $options;
    }
}
