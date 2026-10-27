<?php

namespace App\Models;

use Database\Factories\OutreachSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $active
 */
class OutreachSource extends Model
{
    /** @use HasFactory<OutreachSourceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Baznas and CSR use the Mustahik and Kifayah forms; Bangga Surabaya does
     * not (Workflow v2.1 sections 9.1 to 9.3).
     */
    public function usesMustahikKifayah(): bool
    {
        return $this->code !== 'BANGGA_SURABAYA';
    }
}
