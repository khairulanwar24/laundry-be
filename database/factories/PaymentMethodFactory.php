<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
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
        $category = fake()->randomElement([
            PaymentMethod::CATEGORY_CASH,
            PaymentMethod::CATEGORY_TRANSFER,
            PaymentMethod::CATEGORY_E_WALLET,
        ]);

        $name = match ($category) {
            PaymentMethod::CATEGORY_CASH => 'Tunai',
            PaymentMethod::CATEGORY_TRANSFER => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            PaymentMethod::CATEGORY_E_WALLET => fake()->randomElement(['GoPay', 'OVO', 'DANA', 'ShopeePay']),
        };

        return [
            'outlet_id' => \Database\Factories\OutletFactory::new(),
            'category' => $category,
            'name' => $name,
            'logo' => null,
            'owner_name' => $category === PaymentMethod::CATEGORY_TRANSFER ? fake()->name() : null,
            'tags' => fake()->randomElements(['populer', 'cepat', 'mudah'], fake()->numberBetween(0, 2)),
            'is_active' => true,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
            'owner_name' => null,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => PaymentMethod::CATEGORY_TRANSFER,
            'name' => fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'owner_name' => fake()->name(),
        ]);
    }

    public function eWallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => PaymentMethod::CATEGORY_E_WALLET,
            'name' => fake()->randomElement(['GoPay', 'OVO', 'DANA', 'ShopeePay']),
            'owner_name' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
