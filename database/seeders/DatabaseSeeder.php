<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Accounting\DemoTransactionsSeeder;
use Database\Seeders\Contacts\CustomerSeeder;
use Database\Seeders\Contacts\VendorSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(ChartOfAccountsSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(VendorSeeder::class);
        $this->call(DemoTransactionsSeeder::class);
    }
}
