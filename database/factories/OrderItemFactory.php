<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = fake()->randomElement(['kg', 'pcs', 'meter']);
        $qty = match ($unit) {
            'pcs' => fake()->numberBetween(1, 20), // Integer for pieces
            'kg' => fake()->randomFloat(2, 0.5, 15), // Decimal for weight
            'meter' => fake()->randomFloat(2, 0.5, 10), // Decimal for length
        };
        $pricePerUnit = fake()->randomFloat(2, 5000, 25000);
        $lineTotal = $qty * $pricePerUnit;

        return [
            'order_id' => OrderFactory::new(),
            'service_variant_id' => ServiceVariantFactory::new(),
            'unit' => $unit,
            'qty' => $qty,
            'price_per_unit_snapshot' => $pricePerUnit,
            'line_total' => $lineTotal,
            'note' => fake()->optional(0.2)->sentence(),
        ];
    }
}
