<?php

namespace App\Http\Requests\Outreach;

use App\Support\OutreachFormSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOutreachStepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('outreach'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $step = $this->stepKey();

        return [
            'step' => ['required', Rule::in(array_keys(OutreachFormSchema::steps()))],
            'edit_version' => ['required', 'integer', 'min:0'],
            'next' => ['sometimes', 'boolean'],
            ...($step ? OutreachFormSchema::rules($step, $this->route('outreach')) : []),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $step = $this->stepKey();

        return $step
            ? array_map(fn (array $field) => $field['label'], OutreachFormSchema::steps()[$step]['fields'])
            : [];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh setelah Tanggal Outreach.',
        ];
    }

    /**
     * The validated values of the step's fields, cast to their stored types.
     *
     * @return array<string, mixed>
     */
    public function stepData(): array
    {
        $fields = OutreachFormSchema::steps()[$this->stepKey()]['fields'];
        $validated = $this->validated();
        $data = [];

        foreach ($fields as $name => $field) {
            $value = $validated[$name] ?? null;

            $data[$name] = match (true) {
                $value === null => null,
                $field['input'] === 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                in_array($field['input'], ['integer', 'money'], true) => (int) $value,
                $name === 'desil_1_10' => (int) $value,
                default => $value,
            };
        }

        return $data;
    }

    private function stepKey(): ?string
    {
        $step = $this->input('step');

        return is_string($step) && array_key_exists($step, OutreachFormSchema::steps()) ? $step : null;
    }
}
