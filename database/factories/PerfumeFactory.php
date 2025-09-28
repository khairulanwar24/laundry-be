<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Perfume>
 */
class PerfumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outlet_id' => \Database\Factories\OutletFactory::new(),
            'name' => fake()->randomElement([
                'Molto Blue',
                'Downy Mystique',
                'Lavender Fresh',
                'Rose Garden',
                'Ocean Breeze',
                'Vanilla Dream',
                'Citrus Burst',
                'Floral Paradise',
                'Fresh Cotton',
                'Spring Meadow',
            ]),
            'note' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(95), // 95% chance true
        ];
    }
}
