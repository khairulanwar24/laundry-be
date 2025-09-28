<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Perfume;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Models\UserOutlet;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $owner = User::factory()->create([
            'name' => 'Khan',
            'email' => 'owner@example.com',
            'phone' => '081234567890',
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        // Create sample outlet
        $outlet = \App\Models\Outlet::factory()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Laundry Berkah Jaya',
            'address' => 'Jl. Contoh No. 123, Jakarta Selatan',
            'phone' => '021-12345678',
        ]);

        // Attach owner to outlet
        UserOutlet::create([
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'role' => UserOutlet::ROLE_OWNER,
            'permissions_json' => [],
            'is_active' => true,
        ]);

        // Create sample payment methods for the outlet
        PaymentMethod::create([
            'outlet_id' => $outlet->id,
            'category' => PaymentMethod::CATEGORY_CASH,
            'name' => 'Tunai',
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'outlet_id' => $outlet->id,
            'category' => PaymentMethod::CATEGORY_TRANSFER,
            'name' => 'BCA',
            'owner_name' => 'John Doe',
            'tags' => ['populer', 'cepat'],
            'is_active' => true,
        ]);

        PaymentMethod::create([
            'outlet_id' => $outlet->id,
            'category' => PaymentMethod::CATEGORY_E_WALLET,
            'name' => 'GoPay',
            'tags' => ['mudah'],
            'is_active' => true,
        ]);

        // Create sample Service "Kiloan" with variants
        $service = Service::create([
            'outlet_id' => $outlet->id,
            'name' => 'Kiloan',
            'priority_score' => 90,
            'process_steps_json' => ['cuci', 'kering', 'setrika'],
            'is_active' => true,
        ]);

        // Create 3 variants for the service
        ServiceVariant::create([
            'service_id' => $service->id,
            'name' => 'Reguler',
            'unit' => ServiceVariant::UNIT_KG,
            'price_per_unit' => 6000,
            'tat_duration_hours' => 72,
            'is_active' => true,
        ]);

        ServiceVariant::create([
            'service_id' => $service->id,
            'name' => 'Ekspres',
            'unit' => ServiceVariant::UNIT_KG,
            'price_per_unit' => 8000,
            'tat_duration_hours' => 24,
            'is_active' => true,
        ]);

        ServiceVariant::create([
            'service_id' => $service->id,
            'name' => 'Kilat',
            'unit' => ServiceVariant::UNIT_KG,
            'price_per_unit' => 12000,
            'tat_duration_hours' => 6,
            'is_active' => true,
        ]);

        // Create sample Perfume
        Perfume::create([
            'outlet_id' => $outlet->id,
            'name' => 'Molto Blue',
            'note' => 'Wangi segar dan tahan lama',
            'is_active' => true,
        ]);

        // Create sample Discount
        Discount::create([
            'outlet_id' => $outlet->id,
            'name' => 'Promo Pembuka',
            'type' => Discount::TYPE_PERCENT,
            'value' => 10,
            'note' => 'Diskon 10% untuk pelanggan baru',
            'is_active' => true,
        ]);
    }
}
