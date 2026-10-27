<?php

namespace App\Http\Requests\Outreach;

use App\Models\Outreach;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSourceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('changeSourceType', $this->route('outreach'));
    }

    /**
     * Get the validation rules that apply to the request. A new choice must be
     * active; the current one stays valid even if it was deactivated since.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Outreach $outreach */
        $outreach = $this->route('outreach');
        $activeOr = fn (int $current) => fn (Builder $query) => $query->where(
            fn (Builder $query) => $query->where('active', true)->orWhere('id', $current),
        );

        return [
            'source_id' => ['required', 'integer', Rule::exists('outreach_sources', 'id')->where($activeOr($outreach->source_id))],
            'type_id' => ['required', 'integer', Rule::exists('outreach_types', 'id')->where($activeOr($outreach->type_id))],
            'edit_version' => ['required', 'integer', 'min:0'],
            'confirm_destructive' => ['sometimes', 'boolean'],
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
        ];
    }
}
