<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'method_id' => PaymentMethodFactory::new(),
            'amount' => fake()->randomFloat(2, 10000, 200000),
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'ref_no' => fake()->optional(0.7)->regexify('[A-Z0-9]{10,15}'),
            'note' => fake()->optional(0.3)->sentence(),
            'status' => fake()->randomElement(['SUCCESS', 'VOID']),
        ];
    }
}
