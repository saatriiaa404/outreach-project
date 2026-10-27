<?php

namespace App\Http\Requests\Outreach;

use App\Models\Outreach;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutreachRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Outreach::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_id' => ['required', 'integer', Rule::exists('outreach_sources', 'id')->where('active', true)],
            'type_id' => ['required', 'integer', Rule::exists('outreach_types', 'id')->where('active', true)],
            'outreach_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_id' => 'Source Outreach',
            'type_id' => 'Jenis Outreach',
            'outreach_date' => 'Tanggal Outreach',
        ];
    }
}
