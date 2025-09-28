<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 200000);
        $discountValue = fake()->randomFloat(2, 0, $subtotal * 0.3);
        $total = max(0, $subtotal - $discountValue);

        $checkinAt = fake()->dateTimeBetween('-30 days', 'now');
        $etaAt = fake()->dateTimeBetween($checkinAt, '+7 days');

        return [
            'outlet_id' => OutletFactory::new(),
            'customer_id' => CustomerFactory::new(),
            'invoice_no' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => fake()->randomElement(['ANTRIAN', 'PROSES', 'SIAP_DIAMBIL', 'SELESAI', 'BATAL']),
            'payment_status' => fake()->randomElement(['UNPAID', 'PAID']),
            'payment_method_id' => null, // Will be set by relationship
            'perfume_id' => null, // Will be set by relationship
            'discount_id' => null, // Will be set by relationship
            'discount_value_snapshot' => $discountValue,
            'subtotal' => $subtotal,
            'total' => $total,
            'notes' => fake()->optional(0.3)->sentence(),
            'checkin_at' => $checkinAt,
            'eta_at' => $etaAt,
            'finished_at' => null,
            'canceled_at' => null,
            'collected_at' => null,
            'collected_by_user_id' => null,
            'created_by' => UserFactory::new(),
        ];
    }
}
