<?php

namespace App\Support;

use App\Models\KifayahAnswer;
use App\Models\KifayahForm;
use App\Models\MustahikAnswer;
use App\Models\MustahikForm;
use App\Models\Outreach;
use Carbon\CarbonInterface;

/**
 * Builds snapshot_data, schema version 1 (D-051, migration-design 3.12.1):
 * raw values keyed by column name, coded choices with their labels, no
 * derived values. The Draft preview uses the same shape, so preview and
 * historical documents render from one structure.
 */
class OutreachSnapshot
{
    public const SCHEMA_VERSION = 1;

    private const HOUSE = ['luas_bangunan', 'jenis_lantai', 'jenis_dinding', 'jenis_atap', 'daya_listrik', 'sumber_air'];

    private const ASSETS = [
        'jumlah_sepeda_angin', 'jumlah_sepeda_listrik', 'jumlah_becak', 'jumlah_sepeda_motor', 'jumlah_roda_4',
        'jumlah_kendaraan_lainnya', 'nama_kendaraan_lainnya', 'perhiasan', 'aset_lainnya',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(Outreach $outreach, ?int $versionNumber = null, ?CarbonInterface $finalizedAt = null): array
    {
        $outreach->loadMissing(['source', 'type', 'petugas', 'mustahikForm.answers', 'mustahikForm.petugas', 'kifayahForm.answers']);
        $usesForms = $outreach->source->usesMustahikKifayah();
        $steps = OutreachFormSchema::steps();
        $mck = $outreach->getAttribute('mck_layak');

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'meta' => [
                'outreach_id' => $outreach->id,
                'outreach_number' => $outreach->outreach_number,
                'draft_code' => $outreach->draft_code,
                'version_number' => $versionNumber,
                'outreach_date' => $outreach->outreach_date->toDateString(),
                'source' => ['id' => $outreach->source->id, 'code' => $outreach->source->code, 'name' => $outreach->source->name],
                'type' => ['id' => $outreach->type->id, 'code' => $outreach->type->code, 'name' => $outreach->type->name],
                'petugas' => ['id' => $outreach->petugas->id, 'name' => $outreach->petugas->name],
                'wilayah' => config('app.wilayah'),
                'finalized_at' => $finalizedAt?->toIso8601String(),
            ],
            'client' => self::client($outreach, array_keys($steps['klien']['fields'])),
            'family_income' => self::columns($outreach, self::group($steps['keluarga']['fields'], 'pendapatan')),
            'expenses' => self::columns($outreach, self::group($steps['keluarga']['fields'], 'pengeluaran')),
            'house' => [
                ...self::columns($outreach, self::HOUSE),
                'mck_layak' => $mck === null ? null : ['code' => $mck, 'label' => OutreachFormSchema::MCK_OPTIONS[$mck] ?? null],
            ],
            'assets' => self::columns($outreach, self::ASSETS),
            'narrative' => self::columns($outreach, array_keys($steps['narasi']['fields'])),
            'mustahik' => $usesForms ? self::mustahik($outreach->mustahikForm) : null,
            'kifayah' => $usesForms ? self::kifayah($outreach->kifayahForm) : null,
        ];
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private static function client(Outreach $outreach, array $fields): array
    {
        $client = self::columns($outreach, array_values(array_diff($fields, ['desil_1_10'])));
        $client['status_kesejahteraan'] = $outreach->desil_1_10 === null
            ? null
            : ['desil' => $outreach->desil_1_10, 'label' => $outreach->getAttribute('status_kesejahteraan')];

        return $client;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function mustahik(?MustahikForm $form): ?array
    {
        if ($form === null) {
            return null;
        }

        return [
            'survey_date' => $form->survey_date?->toDateString(),
            'petugas' => ['id' => $form->petugas->id, 'name' => $form->petugas->name],
            'kepala_keluarga' => [
                'nama' => $form->kepala_keluarga_nama,
                'nik_no_kk' => $form->kepala_keluarga_nik_no_kk,
                'alamat' => $form->kepala_keluarga_alamat,
                'rt' => $form->kepala_keluarga_rt,
                'rw' => $form->kepala_keluarga_rw,
            ],
            'answers' => self::answers($form->answers, MustahikFormSchema::QUESTIONS),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function kifayah(?KifayahForm $form): ?array
    {
        if ($form === null) {
            return null;
        }

        $values = [];

        foreach (array_keys(KifayahFormSchema::FIELDS) as $name) {
            $values[$name] = $form->getAttribute($name);
        }

        return [...$values, 'answers' => self::answers($form->answers, KifayahFormSchema::QUESTIONS)];
    }

    /**
     * Answers in the order of the source form.
     *
     * @param  iterable<MustahikAnswer|KifayahAnswer>  $answers
     * @param  array<string, array{label: string, options: array<int, string>}>  $questions
     * @return array<string, array{option: string, label: string|null}>
     */
    private static function answers(iterable $answers, array $questions): array
    {
        $byCode = [];

        foreach ($answers as $answer) {
            $byCode[$answer->question_code] = (string) $answer->answer['option'];
        }

        $result = [];

        foreach ($questions as $code => $question) {
            if (isset($byCode[$code])) {
                $result[$code] = ['option' => $byCode[$code], 'label' => $question['options'][(int) $byCode[$code]] ?? null];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return list<string>
     */
    private static function group(array $fields, string $group): array
    {
        return array_keys(array_filter($fields, fn (array $field) => ($field['group'] ?? null) === $group));
    }

    /**
     * Raw column values; dates as YYYY-MM-DD.
     *
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    private static function columns(Outreach $outreach, array $names): array
    {
        $values = [];

        foreach ($names as $name) {
            $value = $outreach->getAttribute($name);
            $values[$name] = $value instanceof CarbonInterface ? $value->toDateString() : $value;
        }

        return $values;
    }
}
