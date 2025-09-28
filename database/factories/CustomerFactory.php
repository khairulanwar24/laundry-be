<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outlet_id' => OutletFactory::new(),
            'name' => fake()->name(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'email' => fake()->boolean(60) ? fake()->unique()->safeEmail() : null,
            'address' => fake()->optional(0.7)->address(),
            'is_active' => fake()->boolean(95), // 95% chance true
        ];
    }
}
