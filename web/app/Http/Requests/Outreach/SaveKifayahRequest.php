<?php

namespace App\Http\Requests\Outreach;

use App\Models\Outreach;
use App\Support\KifayahFormSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveKifayahRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. Only Baznas
     * and CSR use the Kifayah form.
     */
    public function authorize(): bool
    {
        /** @var Outreach $outreach */
        $outreach = $this->route('outreach');

        return $this->user()->can('update', $outreach) && $outreach->source->usesMustahikKifayah();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'edit_version' => ['required', 'integer', 'min:0'],
            'next' => ['sometimes', 'boolean'],
            ...KifayahFormSchema::rules(),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return KifayahFormSchema::attributes();
    }

    /**
     * Validated values; blanks stay NULL and are never read as 0.
     *
     * @return array<string, int|string|null>
     */
    public function formData(): array
    {
        $validated = $this->validated();
        $data = [];

        foreach (array_keys(KifayahFormSchema::FIELDS) as $name) {
            $data[$name] = isset($validated[$name]) ? (int) $validated[$name] : null;
        }

        foreach (array_keys(KifayahFormSchema::QUESTIONS) as $code) {
            $data[$code] = isset($validated[$code]) ? (string) $validated[$code] : null;
        }

        return $data;
    }
}
