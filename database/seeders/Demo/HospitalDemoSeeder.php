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
use Database\Seeders\Security\PermissionSeeder;
use Database\Seeders\Security\RoleSeeder;
use Illuminate\Database\Seeder;

/**
 * Stand-alone demo dataset for a Hospital / Diagnostic Center. Run this on a
 * fresh database instead of the default DatabaseSeeder to get a
 * client-ready demo in one command:
 *
 *   php artisan migrate:fresh
 *   php artisan db:seed --class="Database\Seeders\Demo\HospitalDemoSeeder"
 *
 * Login: admin@hospital-demo.test / password
 */
class HospitalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->seedChartOfAccounts();

        $admin = User::updateOrCreate(
            ['email' => 'admin@hospital-demo.test'],
            ['name' => 'Demo Admin (Hospital)', 'password' => 'password']
        );
        $admin->syncRoles(['Administrator']);

        if (Product::where('sku', 'CON-GEN')->exists()) {
            return;
        }

        $consultationCategory = ProductCategory::updateOrCreate(['name' => 'Consultation Services']);
        $diagnosticCategory = ProductCategory::updateOrCreate(['name' => 'Diagnostic Tests']);
        $pharmacyCategory = ProductCategory::updateOrCreate(['name' => 'Pharmacy']);

        $consultationIncome = Account::where('code', '4001')->first();
        $diagnosticIncome = Account::where('code', '4002')->first();
        $imagingIncome = Account::where('code', '4003')->first();
        $pharmacyIncome = Account::where('code', '4005')->first();
        $medicineCogs = Account::where('code', '5003')->first();
        $medicineInventory = Account::where('code', '1006')->first();
        $receivable = Account::where('code', '1004')->first();
        $cash = Account::where('code', '1001')->first();
        $bank = Account::where('code', '1002')->first();
        $doctorFee = Account::where('code', '5001')->first();
        $utilities = Account::where('code', '5004')->first();

        app(CreateProduct::class)->handle([
            'sku' => 'CON-GEN', 'name' => 'Consultation — General Physician', 'product_category_id' => $consultationCategory->id,
            'type' => 'service', 'unit' => 'visit', 'purchase_price' => 0, 'selling_price' => 500,
            'income_account_id' => $consultationIncome->id, 'is_active' => true,
        ]);

        $specialist = app(CreateProduct::class)->handle([
            'sku' => 'CON-SPE', 'name' => 'Consultation — Specialist', 'product_category_id' => $consultationCategory->id,
            'type' => 'service', 'unit' => 'visit', 'purchase_price' => 0, 'selling_price' => 1000,
            'income_account_id' => $consultationIncome->id, 'is_active' => true,
        ]);

        $cbc = app(CreateProduct::class)->handle([
            'sku' => 'LAB-CBC', 'name' => 'Lab Test — CBC (Complete Blood Count)', 'product_category_id' => $diagnosticCategory->id,
            'type' => 'service', 'unit' => 'test', 'purchase_price' => 0, 'selling_price' => 400,
            'income_account_id' => $diagnosticIncome->id, 'is_active' => true,
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'LAB-XRAY', 'name' => 'X-Ray — Chest', 'product_category_id' => $diagnosticCategory->id,
            'type' => 'service', 'unit' => 'test', 'purchase_price' => 0, 'selling_price' => 800,
            'income_account_id' => $imagingIncome->id, 'is_active' => true,
        ]);

        $paracetamol = app(CreateProduct::class)->handle([
            'sku' => 'PHR-PARA', 'name' => 'Paracetamol 500mg (Box)', 'product_category_id' => $pharmacyCategory->id,
            'type' => 'inventory', 'unit' => 'box', 'purchase_price' => 60, 'selling_price' => 90,
            'income_account_id' => $pharmacyIncome->id, 'cogs_account_id' => $medicineCogs->id,
            'inventory_account_id' => $medicineInventory->id, 'low_stock_threshold' => 20, 'is_active' => true,
            'opening_quantity' => 200, 'opening_date' => now()->subDays(15)->toDateString(),
        ]);

        app(AdjustStock::class)->handle([
            'product_id' => $paracetamol->id,
            'counted_quantity' => 195,
            'date' => now()->subDays(1)->toDateString(),
            'reference' => 'Demo physical count',
            'notes' => 'Demo stock adjustment seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);

        $patients = [
            ['name' => 'Md. Kamal Hossain', 'email' => 'kamal.hossain@example.com', 'phone' => '+880-1700-300001', 'opening_balance' => 0],
            ['name' => 'Nasima Begum', 'email' => 'nasima.begum@example.com', 'phone' => '+880-1700-300002', 'opening_balance' => 1200],
            ['name' => 'Abdul Karim', 'email' => 'abdul.karim@example.com', 'phone' => '+880-1700-300003', 'opening_balance' => 0],
        ];

        foreach ($patients as $patient) {
            Customer::updateOrCreate(['name' => $patient['name']], $patient);
        }

        $patient = Customer::where('name', 'Nasima Begum')->first();

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $patient->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Demo invoice seeded for verification purposes.',
            'created_by' => $admin->id,
            'items' => [
                ['product_id' => $specialist->id, 'account_id' => $consultationIncome->id, 'description' => 'Consultation — Specialist', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0],
                ['product_id' => $cbc->id, 'account_id' => $diagnosticIncome->id, 'description' => 'Lab Test — CBC', 'quantity' => 1, 'unit_price' => 400, 'discount' => 0],
            ],
        ]);

        app(PostInvoice::class)->handle($invoice);

        $doctorFeeCategory = ExpenseCategory::updateOrCreate(['name' => 'Doctor Fee / Honorarium'], ['default_account_id' => $doctorFee->id]);
        $utilitiesCategory = ExpenseCategory::updateOrCreate(['name' => 'Utilities'], ['default_account_id' => $utilities->id]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $doctorFeeCategory->id,
            'account_id' => $doctorFee->id,
            'payment_account_id' => $bank->id,
            'vendor_id' => null,
            'payee' => 'Dr. Rafiqul Islam (Visiting Consultant)',
            'expense_date' => now()->subDays(3)->toDateString(),
            'amount' => 8000,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $utilitiesCategory->id,
            'account_id' => $utilities->id,
            'payment_account_id' => $cash->id,
            'vendor_id' => null,
            'payee' => 'City Power Distribution',
            'expense_date' => now()->subDays(1)->toDateString(),
            'amount' => 4200,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);
    }

    private function seedChartOfAccounts(): void
    {
        foreach ($this->tree() as $type => $groups) {
            foreach ($groups as $code => $definition) {
                $parent = Account::updateOrCreate(
                    ['code' => (string) $code],
                    ['name' => $definition['name'], 'type' => $type, 'parent_id' => null, 'is_active' => true]
                );

                foreach ($definition['children'] ?? [] as $childCode => $childName) {
                    Account::updateOrCreate(
                        ['code' => (string) $childCode],
                        [
                            'name' => $childName,
                            'type' => $type,
                            'parent_id' => $parent->id,
                            'is_active' => true,
                            'is_bank_account' => in_array((string) $childCode, ['1001', '1002', '1003'], true),
                            'is_undeposited_funds' => (string) $childCode === '1008',
                            'opening_balance' => 0,
                        ]
                    );
                }
            }
        }
    }

    private function tree(): array
    {
        return [
            'asset' => [
                1000 => ['name' => 'Assets', 'children' => [
                    1008 => 'Undeposited Funds',
                    1001 => 'Cash',
                    1002 => 'Bank Accounts',
                    1003 => 'Mobile Banking (bKash/Nagad)',
                    1004 => 'Accounts Receivable (Patient Dues)',
                    1005 => 'Medical Equipment',
                    1006 => 'Medicine & Consumables Inventory',
                    1007 => 'Prepaid Expenses',
                ]],
            ],
            'liability' => [
                2000 => ['name' => 'Liabilities', 'children' => [
                    2001 => 'Accounts Payable (Supplier)',
                    2002 => 'Tax/VAT Payable',
                    2003 => 'Advance Payment from Patients',
                ]],
            ],
            'equity' => [
                3000 => ['name' => 'Equity', 'children' => [
                    3001 => "Owner's Equity",
                    3002 => 'Retained Earnings',
                ]],
            ],
            'income' => [
                4000 => ['name' => 'Income', 'children' => [
                    4001 => 'Consultation Fee Income',
                    4002 => 'Diagnostic & Lab Test Income',
                    4003 => 'X-Ray & Imaging Income',
                    4004 => 'Admission / Bed Fee Income',
                    4005 => 'Pharmacy Sales Income',
                ]],
            ],
            'expense' => [
                5000 => ['name' => 'Expenses', 'children' => [
                    5001 => 'Doctor Fee / Honorarium',
                    5002 => 'Nurse & Staff Salaries',
                    5003 => 'Medicine & Consumables Cost',
                    5004 => 'Utilities',
                    5005 => 'Equipment Maintenance',
                    5006 => 'Marketing',
                    5007 => 'Software & Licenses',
                ]],
            ],
        ];
    }
}
