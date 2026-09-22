<?php

namespace Database\Seeders\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use Illuminate\Database\Seeder;

/**
 * Demo journal postings that exercise the full posting engine end to end
 * (Chart of Accounts -> Journal -> Journal Entries -> Customer/Vendor
 * subsidiary ledgers), so the seeded data is genuinely testable/visible
 * rather than just empty tables. Balanced by construction (PostJournal
 * rejects anything else), per Section 62.
 */
class DemoTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        $receivable = Account::where('code', '1003')->first();
        $salesRevenue = Account::where('code', '4001')->first();
        $operatingExpenses = Account::where('code', '5002')->first();
        $payable = Account::where('code', '2001')->first();

        $acme = Customer::where('name', 'Acme Traders')->first();
        $globalSupplies = Vendor::where('name', 'Global Supplies Co')->first();

        if (! $receivable || ! $salesRevenue || ! $operatingExpenses || ! $payable || ! $acme || ! $globalSupplies) {
            return;
        }

        $post = new PostJournal;

        if (! Journal::where('reference', 'DEMO-SALE-0001')->exists()) {
            $post->handle([
                'date' => now()->subDays(10)->toDateString(),
                'reference' => 'DEMO-SALE-0001',
                'description' => 'Demo: sale on account to Acme Traders',
                'created_by' => null,
                'lines' => [
                    [
                        'account_id' => $receivable->id,
                        'customer_id' => $acme->id,
                        'debit' => 2000,
                        'credit' => 0,
                        'description' => 'Sale on account',
                    ],
                    [
                        'account_id' => $salesRevenue->id,
                        'debit' => 0,
                        'credit' => 2000,
                        'description' => 'Sale on account',
                    ],
                ],
            ]);
        }

        if (! Journal::where('reference', 'DEMO-EXPENSE-0001')->exists()) {
            $post->handle([
                'date' => now()->subDays(5)->toDateString(),
                'reference' => 'DEMO-EXPENSE-0001',
                'description' => 'Demo: office supplies on account from Global Supplies Co',
                'created_by' => null,
                'lines' => [
                    [
                        'account_id' => $operatingExpenses->id,
                        'debit' => 1500,
                        'credit' => 0,
                        'description' => 'Office supplies',
                    ],
                    [
                        'account_id' => $payable->id,
                        'vendor_id' => $globalSupplies->id,
                        'debit' => 0,
                        'credit' => 1500,
                        'description' => 'Office supplies',
                    ],
                ],
            ]);
        }
    }
}
