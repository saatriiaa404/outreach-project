<?php

namespace App\Http\Requests\Outreach;

use App\Enums\AttachmentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Largest accepted upload before compression, in kilobytes (D-047).
     */
    public const MAX_KILOBYTES = 10 * 1024;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('outreach'));
    }

    /**
     * Get the validation rules that apply to the request. The content type is
     * checked from the file bytes by AttachFileToOutreach.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(AttachmentCategory::class)],
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES],
            'title' => ['nullable', 'string', 'max:255'],
            'replaces' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
            'file.uploaded' => 'Berkas gagal diunggah. Periksa koneksi lalu coba lagi.',
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
            'category' => 'Kategori lampiran',
            'file' => 'Berkas',
            'title' => 'Keterangan',
        ];
    }
}
