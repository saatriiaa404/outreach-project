<?php

namespace Database\Factories;

use App\Enums\OutreachStatus;
use App\Models\Outreach;
use App\Models\OutreachSource;
use App\Models\OutreachType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Outreach>
 */
class OutreachFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'draft_code' => 'DRAFT-'.Str::upper(Str::random(12)),
            'source_id' => OutreachSource::factory(),
            'type_id' => OutreachType::factory(),
            'outreach_date' => fake()->date(),
            'petugas_id' => User::factory(),
            'status' => OutreachStatus::Draft,
        ];
    }

    /**
     * Indicate that the Outreach is in the given business state.
     */
    public function status(OutreachStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
