<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
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
            'name' => fake()->randomElement(['Kiloan', 'Satuan', 'Setrika', 'Dry Clean', 'Sepatu', 'Tas']),
            'priority_score' => fake()->numberBetween(10, 100),
            'process_steps_json' => fake()->randomElement([
                ['cuci', 'kering', 'setrika'],
                ['cuci', 'kering'],
                ['setrika'],
                ['dry_clean', 'kering'],
            ]),
            'is_active' => fake()->boolean(85), // 85% chance true
        ];
    }
}
