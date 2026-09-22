<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Accounting\DemoTransactionsSeeder;
use Database\Seeders\Banking\TransferSeeder;
use Database\Seeders\Contacts\CustomerSeeder;
use Database\Seeders\Contacts\VendorSeeder;
use Database\Seeders\Expenses\ExpenseCategorySeeder;
use Database\Seeders\Expenses\ExpenseSeeder;
use Database\Seeders\Inventory\ProductCategorySeeder;
use Database\Seeders\Inventory\ProductSeeder;
use Database\Seeders\Purchases\BillSeeder;
use Database\Seeders\Purchases\VendorPaymentSeeder;
use Database\Seeders\Sales\InvoiceSeeder;
use Database\Seeders\Sales\PaymentSeeder;
use Database\Seeders\Security\PermissionSeeder;
use Database\Seeders\Security\RoleSeeder;
use Database\Seeders\Tax\TaxDemoInvoiceSeeder;
use Database\Seeders\Tax\TaxRateSeeder;
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

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $accountantUser = User::factory()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@example.com',
        ]);

        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $testUser->assignRole('Administrator');
        $accountantUser->assignRole('Accountant');

        $this->call(ChartOfAccountsSeeder::class);
        $this->call(TaxRateSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(VendorSeeder::class);
        $this->call(DemoTransactionsSeeder::class);
        $this->call(InvoiceSeeder::class);
        $this->call(TaxDemoInvoiceSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(BillSeeder::class);
        $this->call(VendorPaymentSeeder::class);
        $this->call(ExpenseCategorySeeder::class);
        $this->call(ExpenseSeeder::class);
        $this->call(TransferSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(ProductSeeder::class);
    }
}
