<?php

namespace Database\Seeders\Demo;

use App\Actions\Expenses\RecordExpense;
use App\Actions\Inventory\AdjustStock;
use App\Actions\Inventory\CreateProduct;
use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\User;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Security\PermissionSeeder;
use Database\Seeders\Security\RoleSeeder;
use Illuminate\Database\Seeder;

/**
 * Stand-alone demo dataset for a Domain / Hosting / Networking service
 * business. Run this on a fresh database instead of the default
 * DatabaseSeeder to get a client-ready demo in one command:
 *
 *   php artisan migrate:fresh
 *   php artisan db:seed --class="Database\Seeders\Demo\DomainHostingDemoSeeder"
 *
 * Login: admin@hosting-demo.test / password
 */
class DomainHostingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ChartOfAccountsSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@hosting-demo.test'],
            ['name' => 'Demo Admin (EasyIT Hosting)', 'password' => 'password']
        );
        $admin->syncRoles(['Administrator']);

        if (Product::where('sku', 'DOM-001')->exists()) {
            return;
        }

        $hostingCategory = ProductCategory::updateOrCreate(['name' => 'Hosting & Domain Services']);
        $networkingCategory = ProductCategory::updateOrCreate(['name' => 'Networking Services']);
        $hardwareCategory = ProductCategory::updateOrCreate(['name' => 'Hardware & Equipment']);

        $domainIncome = Account::where('code', '4001')->first();
        $networkingIncome = Account::where('code', '4002')->first();
        $maintenanceIncome = Account::where('code', '4004')->first();
        $hardwareIncome = Account::where('code', '4005')->first();
        $hardwareCogs = Account::where('code', '5003')->first();
        $hardwareInventory = Account::where('code', '1005')->first();
        $receivable = Account::where('code', '1004')->first();
        $cash = Account::where('code', '1001')->first();
        $bank = Account::where('code', '1002')->first();
        $bandwidthCost = Account::where('code', '5001')->first();
        $officeRent = Account::where('code', '5005')->first();

        $domain = app(CreateProduct::class)->handle([
            'sku' => 'DOM-001',
            'name' => 'Domain Registration (.com / .com.bd)',
            'product_category_id' => $hostingCategory->id,
            'type' => 'service',
            'unit' => 'year',
            'purchase_price' => 800,
            'selling_price' => 1200,
            'income_account_id' => $domainIncome->id,
            'is_active' => true,
        ]);

        $hosting = app(CreateProduct::class)->handle([
            'sku' => 'HOST-001',
            'name' => 'Web Hosting — Business Plan',
            'product_category_id' => $hostingCategory->id,
            'type' => 'service',
            'unit' => 'year',
            'purchase_price' => 3000,
            'selling_price' => 6000,
            'income_account_id' => $domainIncome->id,
            'is_active' => true,
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'NET-001',
            'name' => 'Office Network Setup (LAN + WiFi)',
            'product_category_id' => $networkingCategory->id,
            'type' => 'service',
            'unit' => 'project',
            'purchase_price' => 0,
            'selling_price' => 15000,
            'income_account_id' => $networkingIncome->id,
            'is_active' => true,
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'MNT-001',
            'name' => 'Server Monthly Maintenance',
            'product_category_id' => $networkingCategory->id,
            'type' => 'service',
            'unit' => 'month',
            'purchase_price' => 0,
            'selling_price' => 2500,
            'income_account_id' => $maintenanceIncome->id,
            'is_active' => true,
        ]);

        $router = app(CreateProduct::class)->handle([
            'sku' => 'RTR-001',
            'name' => 'TP-Link Router AC1200',
            'product_category_id' => $hardwareCategory->id,
            'type' => 'inventory',
            'unit' => 'pcs',
            'purchase_price' => 2500,
            'selling_price' => 3200,
            'income_account_id' => $hardwareIncome->id,
            'cogs_account_id' => $hardwareCogs->id,
            'inventory_account_id' => $hardwareInventory->id,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'opening_quantity' => 20,
            'opening_date' => now()->subDays(20)->toDateString(),
        ]);

        app(AdjustStock::class)->handle([
            'product_id' => $router->id,
            'counted_quantity' => 18,
            'date' => now()->subDays(1)->toDateString(),
            'reference' => 'Demo physical count',
            'notes' => 'Demo stock adjustment seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);

        $customers = [
            ['name' => 'Bright Corner IT Ltd', 'email' => 'accounts@brightcorner.example', 'phone' => '+880-1700-100001', 'opening_balance' => 8000],
            ['name' => 'Silver Line Traders', 'email' => 'info@silverline.example', 'phone' => '+880-1700-100002', 'opening_balance' => 0],
            ['name' => 'Nabila Fashion House', 'email' => 'billing@nabilafashion.example', 'phone' => '+880-1700-100003', 'opening_balance' => 4500],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['name' => $customer['name']], $customer);
        }

        $client = Customer::where('name', 'Bright Corner IT Ltd')->first();

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $client->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(25)->toDateString(),
            'notes' => 'Demo invoice seeded for verification purposes.',
            'created_by' => $admin->id,
            'items' => [
                ['product_id' => $hosting->id, 'account_id' => $domainIncome->id, 'description' => 'Web Hosting — Business Plan (1 year)', 'quantity' => 1, 'unit_price' => 6000, 'discount' => 0],
                ['product_id' => $domain->id, 'account_id' => $domainIncome->id, 'description' => 'Domain Registration (1 year)', 'quantity' => 1, 'unit_price' => 1200, 'discount' => 0],
            ],
        ]);

        app(PostInvoice::class)->handle($invoice);

        $bandwidthCategory = ExpenseCategory::updateOrCreate(['name' => 'Bandwidth & Server Cost'], ['default_account_id' => $bandwidthCost->id]);
        $officeRentCategory = ExpenseCategory::updateOrCreate(['name' => 'Office Rent & Utilities'], ['default_account_id' => $officeRent->id]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $bandwidthCategory->id,
            'account_id' => $bandwidthCost->id,
            'payment_account_id' => $cash->id,
            'vendor_id' => null,
            'payee' => 'Cloud Bandwidth Provider',
            'expense_date' => now()->subDays(3)->toDateString(),
            'amount' => 4500,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $officeRentCategory->id,
            'account_id' => $officeRent->id,
            'payment_account_id' => $bank->id,
            'vendor_id' => null,
            'payee' => 'Office Landlord',
            'expense_date' => now()->subDays(6)->toDateString(),
            'amount' => 20000,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);
    }
}
