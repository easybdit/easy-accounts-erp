<?php

namespace Database\Seeders\Demo;

use App\Actions\Expenses\RecordExpense;
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
 * Stand-alone demo dataset for a School / College / University. Run this on
 * a fresh database instead of the default DatabaseSeeder to get a
 * client-ready demo in one command:
 *
 *   php artisan migrate:fresh
 *   php artisan db:seed --class="Database\Seeders\Demo\EducationDemoSeeder"
 *
 * Login: admin@school-demo.test / password
 */
class EducationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->seedChartOfAccounts();

        $admin = User::updateOrCreate(
            ['email' => 'admin@school-demo.test'],
            ['name' => 'Demo Admin (School)', 'password' => 'password']
        );
        $admin->syncRoles(['Administrator']);

        if (Product::where('sku', 'ADM-001')->exists()) {
            return;
        }

        $academicCategory = ProductCategory::updateOrCreate(['name' => 'Academic Fees']);
        $hostelCategory = ProductCategory::updateOrCreate(['name' => 'Hostel & Transport Fees']);

        $admissionIncome = Account::where('code', '4001')->first();
        $tuitionIncome = Account::where('code', '4002')->first();
        $examIncome = Account::where('code', '4003')->first();
        $hostelIncome = Account::where('code', '4004')->first();
        $transportIncome = Account::where('code', '4005')->first();
        $receivable = Account::where('code', '1004')->first();
        $cash = Account::where('code', '1001')->first();
        $bank = Account::where('code', '1002')->first();
        $teacherSalary = Account::where('code', '5001')->first();
        $utilities = Account::where('code', '5003')->first();

        app(CreateProduct::class)->handle([
            'sku' => 'ADM-001', 'name' => 'Admission Fee', 'product_category_id' => $academicCategory->id,
            'type' => 'service', 'unit' => 'each', 'purchase_price' => 0, 'selling_price' => 5000,
            'income_account_id' => $admissionIncome->id, 'is_active' => true,
        ]);

        $tuition = app(CreateProduct::class)->handle([
            'sku' => 'TUI-001', 'name' => 'Tuition Fee — Monthly', 'product_category_id' => $academicCategory->id,
            'type' => 'service', 'unit' => 'month', 'purchase_price' => 0, 'selling_price' => 1500,
            'income_account_id' => $tuitionIncome->id, 'is_active' => true,
        ]);

        $exam = app(CreateProduct::class)->handle([
            'sku' => 'EXM-001', 'name' => 'Exam Fee — Term', 'product_category_id' => $academicCategory->id,
            'type' => 'service', 'unit' => 'term', 'purchase_price' => 0, 'selling_price' => 800,
            'income_account_id' => $examIncome->id, 'is_active' => true,
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'HOS-001', 'name' => 'Hostel Fee — Monthly', 'product_category_id' => $hostelCategory->id,
            'type' => 'service', 'unit' => 'month', 'purchase_price' => 0, 'selling_price' => 3500,
            'income_account_id' => $hostelIncome->id, 'is_active' => true,
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'TRN-001', 'name' => 'Transport Fee — Monthly', 'product_category_id' => $hostelCategory->id,
            'type' => 'service', 'unit' => 'month', 'purchase_price' => 0, 'selling_price' => 1200,
            'income_account_id' => $transportIncome->id, 'is_active' => true,
        ]);

        $students = [
            ['name' => 'Rakib Hasan (Class 8)', 'email' => 'guardian.rakib@example.com', 'phone' => '+880-1700-200001', 'opening_balance' => 0],
            ['name' => 'Ayesha Siddika (Class 10)', 'email' => 'guardian.ayesha@example.com', 'phone' => '+880-1700-200002', 'opening_balance' => 1500],
            ['name' => 'Tanvir Ahmed (BBA 2nd Year)', 'email' => 'guardian.tanvir@example.com', 'phone' => '+880-1700-200003', 'opening_balance' => 0],
        ];

        foreach ($students as $student) {
            Customer::updateOrCreate(['name' => $student['name']], $student);
        }

        $student = Customer::where('name', 'Ayesha Siddika (Class 10)')->first();

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $student->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(4)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Demo invoice seeded for verification purposes.',
            'created_by' => $admin->id,
            'items' => [
                ['product_id' => $tuition->id, 'account_id' => $tuitionIncome->id, 'description' => 'Tuition Fee — Monthly', 'quantity' => 1, 'unit_price' => 1500, 'discount' => 0],
                ['product_id' => $exam->id, 'account_id' => $examIncome->id, 'description' => 'Exam Fee — Term', 'quantity' => 1, 'unit_price' => 800, 'discount' => 0],
            ],
        ]);

        app(PostInvoice::class)->handle($invoice);

        $teacherSalaryCategory = ExpenseCategory::updateOrCreate(['name' => "Teachers' Salaries"], ['default_account_id' => $teacherSalary->id]);
        $utilitiesCategory = ExpenseCategory::updateOrCreate(['name' => 'Utilities'], ['default_account_id' => $utilities->id]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $teacherSalaryCategory->id,
            'account_id' => $teacherSalary->id,
            'payment_account_id' => $bank->id,
            'vendor_id' => null,
            'payee' => 'Staff Payroll — Teaching Staff',
            'expense_date' => now()->subDays(2)->toDateString(),
            'amount' => 45000,
            'reference' => null,
            'notes' => 'Demo expense seeded for verification purposes.',
            'created_by' => $admin->id,
        ]);

        app(RecordExpense::class)->handle([
            'expense_category_id' => $utilitiesCategory->id,
            'account_id' => $utilities->id,
            'payment_account_id' => $cash->id,
            'vendor_id' => null,
            'payee' => 'Grid Electricity Office',
            'expense_date' => now()->subDays(1)->toDateString(),
            'amount' => 3500,
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
                    1004 => 'Accounts Receivable (Student Dues)',
                    1005 => 'Furniture & Fixtures',
                    1006 => 'Library Books & Lab Equipment',
                    1007 => 'Advance Rent / Prepaid Expenses',
                ]],
            ],
            'liability' => [
                2000 => ['name' => 'Liabilities', 'children' => [
                    2001 => 'Accounts Payable',
                    2002 => 'Tax/VAT Payable',
                    2003 => 'Advance Tuition Fee (Deferred Revenue)',
                ]],
            ],
            'equity' => [
                3000 => ['name' => 'Equity', 'children' => [
                    3001 => "Owner's / Trust Equity",
                    3002 => 'Retained Earnings (Surplus)',
                ]],
            ],
            'income' => [
                4000 => ['name' => 'Income', 'children' => [
                    4001 => 'Admission Fee Income',
                    4002 => 'Tuition Fee Income',
                    4003 => 'Exam Fee Income',
                    4004 => 'Hostel Fee Income',
                    4005 => 'Transport Fee Income',
                ]],
            ],
            'expense' => [
                5000 => ['name' => 'Expenses', 'children' => [
                    5001 => "Teachers' Salaries",
                    5002 => 'Staff Salaries',
                    5003 => 'Utilities (Electricity/Gas/Water)',
                    5004 => 'Library & Lab Supplies',
                    5005 => 'Building Rent & Maintenance',
                    5006 => 'Marketing & Admission Campaign',
                    5007 => 'Software & Licenses (LMS/ERP)',
                ]],
            ],
        ];
    }
}
