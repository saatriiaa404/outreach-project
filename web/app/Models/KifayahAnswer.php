<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One single-choice answer, stored as {"option": "<code>"} (Q-07).
 *
 * @property int $id
 * @property int $kifayah_form_id
 * @property string $question_code
 * @property array{option: string} $answer
 */
class KifayahAnswer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['kifayah_form_id', 'question_code', 'answer'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer' => 'array',
        ];
    }
}
