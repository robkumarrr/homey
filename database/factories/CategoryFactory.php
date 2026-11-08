<?php

namespace Database\Factories;

use App\Enums\CategoryColour;
use App\Enums\CategoryIcon;
use App\Models\Category;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'icon' => fake()->randomElement(CategoryIcon::cases()),
            'colour' => fake()->randomElement(CategoryColour::cases()),
            'position' => 0,
        ];
    }

    /**
     * Indicate that the category has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
