<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserOutlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserOutlet>
 */
class UserOutletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'outlet_id' => \Database\Factories\OutletFactory::new(),
            'role' => fake()->randomElement([UserOutlet::ROLE_OWNER, UserOutlet::ROLE_KARYAWAN]),
            'permissions_json' => [],
            'is_active' => true,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserOutlet::ROLE_OWNER,
        ]);
    }

    public function karyawan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserOutlet::ROLE_KARYAWAN,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
