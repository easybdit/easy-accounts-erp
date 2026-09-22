<?php

namespace Database\Seeders\Contacts;

use App\Models\Contacts\Vendor;
use Illuminate\Database\Seeder;

/**
 * Generic example vendors (Section 57: no client-specific data).
 */
class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['name' => 'Global Supplies Co', 'email' => 'sales@globalsupplies.example', 'phone' => '+880-1800-000001', 'opening_balance' => 3000],
            ['name' => 'City Hardware', 'email' => 'orders@cityhardware.example', 'phone' => '+880-1800-000002', 'opening_balance' => 0],
            ['name' => 'Prime Logistics', 'email' => 'billing@primelogistics.example', 'phone' => '+880-1800-000003', 'opening_balance' => 7500],
        ];

        foreach ($vendors as $vendor) {
            Vendor::updateOrCreate(['name' => $vendor['name']], $vendor);
        }
    }
}
