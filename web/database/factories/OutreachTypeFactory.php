<?php

namespace Database\Factories;

use App\Models\OutreachType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutreachType>
 */
class OutreachTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TYPE_'.fake()->unique()->bothify('????##'),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }
}
