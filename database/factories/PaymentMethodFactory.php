<?php

namespace Database\Factories;

use App\Enums\PaymentMethodKind;
use App\Models\Family;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
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
            'kind' => fake()->randomElement(PaymentMethodKind::cases()),
            'last_four' => fake()->numerify('####'),
            'position' => 0,
        ];
    }

    /**
     * Indicate that the payment method has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
