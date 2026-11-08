<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'category_id' => fn (array $attributes) => Category::factory()->create([
                'family_id' => $attributes['family_id'],
            ]),
            'amount_cents' => fake()->numberBetween(100, 50_000),
            'paid_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'note' => fake()->company(),
        ];
    }
}
