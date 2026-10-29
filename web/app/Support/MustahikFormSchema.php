<?php

namespace App\Support;

use App\Models\MustahikForm;

/**
 * The Mustahik form (mustahik.md): header fields filled by the Petugas and
 * 25 single-choice questions. Labels and options are transcribed literally
 * from the source PDF, including its spelling and overlapping ranges (D-022);
 * do not normalize them. Option codes are the 1-based position on the PDF and
 * are stored as {"option": "<code>"} (migration-design Q-07). No answer is
 * ever selected automatically and nothing is scored.
 */
class MustahikFormSchema
{
    /**
     * Header fields filled by the Petugas; all are required for Finalisasi (D-048).
     *
     * @var array<string, array{label: string, input: string, max?: int}>
     */
    public const HEADER = [
        'survey_date' => ['label' => 'Tanggal survei', 'input' => 'date'],
        'kepala_keluarga_nama' => ['label' => 'Nama Kepala Keluarga', 'input' => 'text', 'max' => 255],
        'kepala_keluarga_nik_no_kk' => ['label' => 'NIK/No KK Kepala Keluarga', 'input' => 'text', 'max' => 32],
        'kepala_keluarga_alamat' => ['label' => 'Alamat Kepala Keluarga (Jalan)', 'input' => 'textarea', 'max' => 15_000],
        'kepala_keluarga_rt' => ['label' => 'RT', 'input' => 'text', 'max' => 5],
        'kepala_keluarga_rw' => ['label' => 'RW', 'input' => 'text', 'max' => 5],
    ];

    /**
     * @var array<string, array{label: string, options: array<int, string>}>
     */
    public const QUESTIONS = [
        'HOUSE_BUILDING_AREA' => ['label' => 'luas Bangunan', 'options' => ['1' => 'sangat Kecil (<4x4)', '2' => 'Kecil (4x 4 - 5x6)', '3' => 'sedang (5 x 7 -5 x 15)', '4' => 'Lebar (>5 x 15)']],
        'HOUSE_WALL_TYPE' => ['label' => 'Jenis Dinding', 'options' => ['1' => 'Triplek/Sesek', '2' => 'Papan', '3' => 'Tembok', '4' => 'Tembok Keramik']],
        'HOUSE_OWNERSHIP_STATUS' => ['label' => 'Status Tempat Tinggal', 'options' => ['1' => 'Kost', '2' => 'Kontrak', '3' => 'Menumpang/keluarga', '4' => 'Milik Sendiri']],
        'HOUSE_MCK' => ['label' => 'Fasilitas MCK', 'options' => ['1' => 'Tidak Punya', '2' => 'WC Umum', '3' => 'Punya Tidak Layak', '4' => 'Punya Layak']],
        'HOUSE_DRINKING_WATER' => ['label' => 'Sumber Air Minum', 'options' => ['1' => 'Bukan Air bersih', '2' => 'Air Bersih (beli)', '3' => 'Sumur', '4' => 'Sumur Air bersih']],
        'HOUSE_KITCHEN' => ['label' => 'Kondisi Dapur', 'options' => ['1' => 'Kayu Bakar', '2' => 'Kompor Minyak', '3' => 'LPG 3 KG', '4' => 'LPG 12 KG']],
        'HOUSE_ASSET_OWNERSHIP' => ['label' => 'Kepemilikan Aset', 'options' => ['1' => 'Tidak Punya', '2' => 'Sawah/Kebun < 500 m2', '3' => 'Sawah/Kebun > 500 m2', '4' => 'Sawah/Kebun > 1000 m2']],
        'HOUSE_VEHICLE_OWNERSHIP' => ['label' => 'Kepemilikan Kendaraan', 'options' => ['1' => 'tidak Punya', '2' => 'Ontel', '3' => 'Sepeda Motr/+Ontel', '4' => 'Mobil']],
        'HOUSE_FLOOR_TYPE' => ['label' => 'Jenis Lantai', 'options' => ['1' => 'Tanah', '2' => 'Plester', '3' => 'Tekel/Ubin', '4' => 'Keramik']],
        'HOUSE_ROOF_TYPE' => ['label' => 'Jenis Atap', 'options' => ['1' => 'Genting/Asbas (bocor)', '2' => 'Asbas', '3' => 'Genting', '4' => 'Dak/cor']],
        'ECONOMIC_HEAD_EDUCATION' => ['label' => 'Pendidikan Kepala Rumah tangga', 'options' => ['1' => 'Tidak Sekolah', '2' => 'Sd-smp', '3' => 'SMA', '4' => 'Perguruan Tinggi']],
        'ECONOMIC_HEAD_OCCUPATION' => ['label' => 'Pekerjaan Kepala Rumah Tangga', 'options' => ['1' => 'Tidak bekerja', '2' => 'Buruh tani/Serabutan', '3' => 'Petani', '4' => 'Karyawan/Tukang']],
        'ECONOMIC_MONTHLY_INCOME' => ['label' => 'Total Pendapatan 1 bulan', 'options' => ['1' => '< 300.000', '2' => '< 750.000,-', '3' => '< 1.500.000', '4' => '> 3.500.000,-']],
        'ECONOMIC_FOOD_PURCHASE' => ['label' => 'Kemampuan Membeli daging/ayam/Susu dalam Seminggu', 'options' => ['1' => 'Tidak Mampu', '2' => '1 kali', '3' => '2 Kali', '4' => '3 Kali']],
        'ECONOMIC_CLOTHING_PURCHASE' => ['label' => 'Kemampuan Membeli Pakaian Baru', 'options' => ['1' => '1 kali Setahun', '2' => '2 Kali Setahun', '3' => '3 Kali Setahun', '4' => '5 Kali Setahun']],
        'ECONOMIC_OTHER_SUBSIDY' => ['label' => 'Asumsi Subsidi Lain', 'options' => ['1' => 'Tidak Ada', '2' => 'Sumbangan Rutin', '3' => 'Kerabat /Orang lain', '4' => 'Anak']],
        'ECONOMIC_MEAL_FREQUENCY' => ['label' => 'Frekuensi Makan dalam sehari', 'options' => ['1' => '1 kali', '2' => '2 Kali', '3' => '3 Kali', '4' => '4 Kali']],
        'ECONOMIC_LIGHTING' => ['label' => 'Jenis Penerangan', 'options' => ['1' => 'Tidak Ada', '2' => 'Listrik 450', '3' => 'Listrik 900', '4' => 'Listrik > 900']],
        'ECONOMIC_SIDE_BUSINESS' => ['label' => 'Usaha Sampingan', 'options' => ['1' => 'Tidak ada', '2' => 'Ada, tidak jalan', '3' => 'Ada, hasil tidak rutin', '4' => 'Ada, hasil rutin']],
        'ECONOMIC_GOVERNMENT_AID' => ['label' => 'Bansos Pemerintah', 'options' => ['1' => 'PKH', '2' => 'BPNT / Raskin', '3' => 'PBI JKN / KIS', '4' => 'Tidak Ada']],
        'PHYSICAL_CONDITION' => ['label' => 'Keadaan Fisik', 'options' => ['1' => 'Manula Sakit', '2' => 'Manula', '3' => 'Cacat Produktif', '4' => 'Sehat/Produktif']],
        'PHYSICAL_DEPENDENTS' => ['label' => 'Tanggungan', 'options' => ['1' => 'Anak Yang Masih Sekolah', '2' => 'Lansia (tidak produktif)', '3' => 'Keluarga lainya', '4' => 'Tidak ada']],
        'PHYSICAL_DEBT' => ['label' => 'Hutang', 'options' => ['1' => 'Rentenir', '2' => 'Nonrentenir', '3' => 'Tidak ada', '4' => 'Bank']],
        'PHYSICAL_HEALTH_CAPABILITY' => ['label' => 'Kemampuan Memenuhi Kesehatan', 'options' => ['1' => 'Tidak ada', '2' => 'KIS/ PBI JKN', '3' => 'BPJS Kelas 3', '4' => 'BPJS Kelas 2']],
        'PHYSICAL_SAVINGS' => ['label' => 'Tabungan', 'options' => ['1' => 'Tidak ada', '2' => 'Emas < 5 grm', '3' => 'Emas > 5 grm', '4' => 'Emas > 10 grm']],
    ];

    /**
     * Draft-save rules: types and known options only, nothing required.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::HEADER as $name => $field) {
            $rules[$name] = $field['input'] === 'date'
                ? ['nullable', 'date_format:Y-m-d']
                : ['nullable', 'string', 'max:'.$field['max']];
        }

        foreach (self::QUESTIONS as $code => $question) {
            $rules[$code] = ['nullable', 'in:'.implode(',', array_keys($question['options']))];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            ...array_map(fn (array $field) => $field['label'], self::HEADER),
            ...array_map(fn (array $question) => $question['label'], self::QUESTIONS),
        ];
    }

    /**
     * What Finalisasi still needs from the Mustahik form: every header field
     * (D-048) and an answer to each of the 25 questions.
     *
     * @return list<string>
     */
    public static function missing(?MustahikForm $form): array
    {
        $missing = [];

        foreach (self::HEADER as $name => $field) {
            $value = $form?->getAttribute($name);

            if ($value === null || $value === '') {
                $missing[] = $field['label'];
            }
        }

        $answered = $form ? $form->answers->pluck('question_code')->all() : [];

        foreach (self::QUESTIONS as $code => $question) {
            if (! in_array($code, $answered, true)) {
                $missing[] = $question['label'];
            }
        }

        return $missing;
    }

    /**
     * Stepper state of the Mustahik step.
     */
    public static function completion(?MustahikForm $form): string
    {
        $missing = count(self::missing($form));

        return match (true) {
            $missing === count(self::HEADER) + count(self::QUESTIONS) => 'untouched',
            $missing > 0 => 'incomplete',
            default => 'complete',
        };
    }
}
