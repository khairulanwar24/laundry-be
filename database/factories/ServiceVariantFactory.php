<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceVariant>
 */
class ServiceVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = fake()->randomElement(['kg', 'pcs', 'meter']);
        $basePrice = match ($unit) {
            'kg' => fake()->numberBetween(5000, 15000),
            'pcs' => fake()->numberBetween(2000, 10000),
            'meter' => fake()->numberBetween(3000, 8000),
        };

        return [
            'service_id' => \App\Models\Service::factory(),
            'name' => fake()->randomElement(['Reguler', 'Ekspres', 'Kilat', 'Premium', 'Ekonomis']),
            'unit' => $unit,
            'price_per_unit' => $basePrice,
            'tat_duration_hours' => fake()->randomElement([6, 12, 24, 48, 72]),
            'image_path' => fake()->optional()->imageUrl(400, 300, 'laundry'),
            'note' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(90), // 90% chance true
        ];
    }
}
