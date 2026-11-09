<?php

namespace Database\Factories;

use App\Enums\RepeatFrequency;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commitment>
 */
class CommitmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'category_id' => fn (array $attributes) => Category::factory()->create(['family_id' => $attributes['family_id']]),
            'name' => fake()->words(2, true),
            'amount_varies' => false,
            'frequency' => RepeatFrequency::Monthly,
            'interval' => 1,
            'starts_on' => '2026-01-15',
        ];
    }

    /**
     * Give the bill an amount from its first due date onward.
     */
    public function costing(int $amountCents): static
    {
        return $this->afterCreating(fn (Commitment $commitment) => $commitment->amounts()->create([
            'effective_on' => $commitment->starts_on,
            'amount_cents' => $amountCents,
        ]));
    }

    /**
     * Indicate that the bill has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
