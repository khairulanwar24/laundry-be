<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['ANTRIAN', 'PROSES', 'SIAP_DIAMBIL', 'SELESAI', 'BATAL'];
        $fromStatus = fake()->optional(0.8)->randomElement($statuses);
        $toStatus = fake()->randomElement($statuses);

        return [
            'order_id' => OrderFactory::new(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'by_user_id' => UserFactory::new(),
            'notes' => fake()->optional(0.4)->sentence(),
            'changed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
