<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['nominal', 'percent']);
        $value = match ($type) {
            'nominal' => fake()->numberBetween(5000, 50000),
            'percent' => fake()->numberBetween(5, 50),
        };

        return [
            'outlet_id' => \Database\Factories\OutletFactory::new(),
            'name' => fake()->randomElement([
                'Promo Pembuka',
                'Diskon Member',
                'Cashback Spesial',
                'Promo Akhir Tahun',
                'Diskon Pelanggan Setia',
                'Promo Weekend',
                'Diskon Volume',
                'Early Bird',
            ]),
            'type' => $type,
            'value' => $value,
            'note' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(80), // 80% chance true
        ];
    }
}
