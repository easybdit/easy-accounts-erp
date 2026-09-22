<?php

namespace Database\Seeders\Purchases;

use App\Actions\Purchases\PostBill;
use App\Actions\Purchases\SaveBillDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use Illuminate\Database\Seeder;

/**
 * A demo bill exercised end to end (draft -> posted -> journal), mirroring
 * Database\Seeders\Sales\InvoiceSeeder for the Purchases side.
 */
class BillSeeder extends Seeder
{
    public function run(): void
    {
        if (Bill::where('bill_number', 'like', 'BILL-%')->exists()) {
            return;
        }

        $vendor = Vendor::where('name', 'City Hardware')->first();
        $payable = Account::where('code', '2001')->first();
        $operatingExpenses = Account::where('code', '5002')->first();

        if (! $vendor || ! $payable || ! $operatingExpenses) {
            return;
        }

        $bill = app(SaveBillDraft::class)->handle([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->addDays(23)->toDateString(),
            'notes' => 'Demo bill seeded for verification purposes.',
            'items' => [
                [
                    'account_id' => $operatingExpenses->id,
                    'description' => 'Office maintenance supplies',
                    'quantity' => 5,
                    'unit_price' => 80,
                    'discount' => 0,
                ],
                [
                    'account_id' => $operatingExpenses->id,
                    'description' => 'Delivery fee',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount' => 0,
                ],
            ],
        ]);

        app(PostBill::class)->handle($bill);
    }
}
