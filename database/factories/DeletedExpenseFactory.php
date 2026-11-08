<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\DeletedExpense;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletedExpense>
 */
class DeletedExpenseFactory extends Factory
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
            'expense_id' => fake()->unique()->numberBetween(1_000, 1_000_000),
            'payload' => fn (array $attributes) => [
                'id' => $attributes['expense_id'],
                'family_id' => $attributes['family_id'],
                'category_id' => Category::factory()->create(['family_id' => $attributes['family_id']])->id,
                'created_by' => null,
                'name' => fake()->company(),
                'amount_cents' => fake()->numberBetween(100, 50_000),
                'paid_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            'deleted_at' => now(),
        ];
    }
}
