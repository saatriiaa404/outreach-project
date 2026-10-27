<?php

namespace App\Http\Requests\Outreach;

use App\Models\Outreach;
use App\Support\MustahikFormSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveMustahikRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. Only Baznas
     * and CSR use the Mustahik form.
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
            ...MustahikFormSchema::rules(),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return MustahikFormSchema::attributes();
    }

    /**
     * Validated header fields and answers; blanks are stored as not filled.
     *
     * @return array<string, string|null>
     */
    public function formData(): array
    {
        $validated = $this->validated();
        $data = [];

        foreach ([...array_keys(MustahikFormSchema::HEADER), ...array_keys(MustahikFormSchema::QUESTIONS)] as $name) {
            $value = $validated[$name] ?? null;
            $data[$name] = $value === null ? null : (string) $value;
        }

        return $data;
    }
}
