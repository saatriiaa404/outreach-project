<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A logical document template package per Source (Database v1.1 10.4, Q-09).
 * Old versions are never changed; activating a new one leaves history intact.
 *
 * @property int $id
 * @property string $template_key
 * @property int $version_number
 * @property string $renderer_key
 * @property array<string, mixed>|null $metadata
 * @property bool $is_active
 */
class DocumentTemplateVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * Template package used by a Source code.
     */
    public static function keyFor(string $sourceCode): string
    {
        return $sourceCode === 'BANGGA_SURABAYA' ? 'PACKAGE_BANGGA_SURABAYA' : 'PACKAGE_BAZNAS_CSR';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'version_number' => 'integer',
        ];
    }
}
