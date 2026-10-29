<?php

namespace Database\Factories;

use App\Models\OutreachSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutreachSource>
 */
class OutreachSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SRC_'.fake()->unique()->bothify('????##'),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }
}
