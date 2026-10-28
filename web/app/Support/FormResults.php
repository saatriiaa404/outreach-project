<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Mustahik and Kifayah answers as label/value rows for the browser, built
 * from snapshot_data (D-051) so the Draft Review and the review of a
 * historical version show the same thing. A null value means "not filled".
 */
class FormResults
{
    /**
     * @param  array<string, mixed>|null  $form  snapshot_data['mustahik']
     * @return array{header: list<array{label: string, value: string|null}>, answers: list<array{label: string, value: string|null}>}
     */
    public static function mustahik(?array $form): array
    {
        $header = [];

        foreach (MustahikFormSchema::HEADER as $name => $field) {
            if ($name === 'survey_date') {
                $date = $form['survey_date'] ?? null;
                $value = $date === null ? null : Carbon::parse($date)->translatedFormat('l, j F Y');
            } else {
                $value = $form['kepala_keluarga'][substr($name, strlen('kepala_keluarga_'))] ?? null;
            }
            $header[] = ['label' => $field['label'], 'value' => $value === '' ? null : $value];
        }

        return ['header' => $header, 'answers' => self::answers(MustahikFormSchema::QUESTIONS, $form['answers'] ?? [])];
    }

    /**
     * Money 0 reads "-" (D-050).
     *
     * @param  array<string, mixed>|null  $form  snapshot_data['kifayah']
     * @return array{fields: list<array{label: string, value: string|null}>, answers: list<array{label: string, value: string|null}>}
     */
    public static function kifayah(?array $form): array
    {
        $fields = [];

        foreach (KifayahFormSchema::FIELDS as $name => $field) {
            $value = $form[$name] ?? null;
            $fields[] = [
                'label' => $field['label'],
                'value' => $field['input'] === 'money' ? OutreachDocument::rupiah($value) : ($value === null ? null : (string) $value),
            ];
        }

        return ['fields' => $fields, 'answers' => self::answers(KifayahFormSchema::QUESTIONS, $form['answers'] ?? [])];
    }

    /**
     * @param  array<string, array{label: string, options: array<int, string>}>  $questions
     * @param  array<string, array{option: string, label: string|null}>  $answers
     * @return list<array{label: string, value: string|null}>
     */
    private static function answers(array $questions, array $answers): array
    {
        $rows = [];

        foreach ($questions as $code => $question) {
            $rows[] = ['label' => $question['label'], 'value' => $answers[$code]['label'] ?? null];
        }

        return $rows;
    }
}
