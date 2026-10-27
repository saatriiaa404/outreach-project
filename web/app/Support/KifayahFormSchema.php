<?php

namespace App\Support;

use App\Models\KifayahForm;

/**
 * The Had Kifayah form (kifayah.md): fixed nominal and count fields, then
 * seven single-choice questions transcribed literally from kifayah.pdf
 * (D-037). Option codes are the 1-based position on the source and are
 * stored as {"option": "<code>"} (Q-07). An empty value means "not filled"
 * and is never turned into 0 (section 2.1); nothing is filled from Outreach.
 */
class KifayahFormSchema
{
    /**
     * Largest accepted rupiah amount; keeps values well inside BIGINT UNSIGNED.
     */
    private const MAX_RUPIAH = 999_999_999_999;

    /**
     * Column limit of SMALLINT UNSIGNED, not a business maximum (kifayah.md 6.6).
     */
    private const MAX_COUNT = 65_535;

    /**
     * @var array<string, array{label: string, input: string, group: string}>
     */
    public const FIELDS = [
        'income_head_family' => ['label' => 'Pendapatan Kepala Keluarga per Bulan', 'input' => 'money', 'group' => 'pendapatan'],
        'income_other_members' => ['label' => 'Pendapatan Anggota Lain per Bulan', 'input' => 'money', 'group' => 'pendapatan'],
        'expense_head_household' => ['label' => 'Kepala Rumah Tangga', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_housewife' => ['label' => 'Ibu Rumah Tangga', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_adult_working' => ['label' => 'Anak Dewasa (Bekerja)', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_adult_not_working' => ['label' => 'Anak Dewasa (Tidak Bekerja)', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_school_sma' => ['label' => 'Anak Sekolah (SMA/Setingkat)', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_school_smp' => ['label' => 'Anak Sekolah (SMP/Setingkat)', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_school_sd' => ['label' => 'Anak Sekolah (SD/Setingkat)', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_baby_not_school' => ['label' => 'Anak/Bayi Belum Sekolah', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_disability' => ['label' => 'Penyandang Disabilitas', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_breastfeeding' => ['label' => 'Ibu Menyusui', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_pregnant' => ['label' => 'Ibu Hamil', 'input' => 'money', 'group' => 'pengeluaran'],
        'expense_widow_not_working' => ['label' => 'Status Janda (Tidak Bekerja)', 'input' => 'money', 'group' => 'pengeluaran'],
        'higher_education_count' => ['label' => 'Jumlah Keluarga yang Sedang Menempuh Pendidikan Tinggi', 'input' => 'integer', 'group' => 'pendidikan'],
    ];

    /**
     * @var array<string, array{section: string, label: string, options: array<int, string>}>
     */
    public const QUESTIONS = [
        'HOUSE_OWNERSHIP' => ['section' => 'STATUS TEMPAT TINGGAL', 'label' => 'Kepemilikan Rumah/Kontrakan', 'options' => [1 => 'Tidak Memiliki Tempat Tinggal', 2 => 'Sewa/Kontrak', 3 => 'Memiliki Tempat Tinggal Sendiri']],
        'ELECTRICITY' => ['section' => 'STATUS TEMPAT TINGGAL', 'label' => 'Penerangan Listrik', 'options' => [1 => '450 Watt', 2 => '900 Watt', 3 => 'Lebih dari 900 Watt']],
        'GOVERNMENT_AID' => ['section' => 'MENERIMA BANTUAN PEMERINTAH', 'label' => 'Bansos', 'options' => [1 => 'Tidak Menerima Bansos', 2 => 'Menerima Bansos']],
        'BPJS_KIS_PBI' => ['section' => 'MENERIMA BANTUAN PEMERINTAH', 'label' => 'Penerima BPJS', 'options' => [1 => 'Bukan Penerima BPJS/KIS/PBI', 2 => 'Penerima BPJS/KIS/PBI']],
        'MOTOR_VEHICLE' => ['section' => 'KEPEMILIKAN ASET', 'label' => 'Memiliki Kendaraan Bermotor', 'options' => [1 => 'Tidak Memiliki Kendaraan Bermotor', 2 => 'Memiliki Kendaraan Bermotor']],
        'SAVINGS' => ['section' => 'KEPEMILIKAN ASET', 'label' => 'Memiliki Tabungan', 'options' => [1 => 'Tidak Memiliki Tabungan', 2 => 'Memiliki Tabungan']],
        'DEBT' => ['section' => 'BEBAN KEUANGAN', 'label' => 'Memiliki Hutang', 'options' => [1 => 'Tidak Memiliki Hutang', 2 => 'Memiliki Hutang']],
    ];

    /**
     * Draft-save rules: numbers of at least 0 and known options; nothing required.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::FIELDS as $name => $field) {
            $rules[$name] = ['nullable', 'integer', 'min:0', 'max:'.($field['input'] === 'money' ? self::MAX_RUPIAH : self::MAX_COUNT)];
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
            ...array_map(fn (array $field) => $field['label'], self::FIELDS),
            ...array_map(fn (array $question) => $question['label'], self::QUESTIONS),
        ];
    }

    /**
     * What Finalisasi still needs: every field filled explicitly (0 counts as
     * filled) and an answer to each of the seven questions (kifayah.md 6).
     *
     * @return list<string>
     */
    public static function missing(?KifayahForm $form): array
    {
        $missing = [];

        foreach (self::FIELDS as $name => $field) {
            if ($form?->getAttribute($name) === null) {
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
     * Stepper state of the Kifayah step.
     */
    public static function completion(?KifayahForm $form): string
    {
        $missing = count(self::missing($form));

        return match (true) {
            $missing === count(self::FIELDS) + count(self::QUESTIONS) => 'untouched',
            $missing > 0 => 'incomplete',
            default => 'complete',
        };
    }
}
