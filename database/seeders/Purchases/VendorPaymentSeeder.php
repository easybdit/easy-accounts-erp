<?php

namespace Database\Seeders\Purchases;

use App\Actions\Purchases\MakePayment;
use App\Models\Accounting\Account;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use Illuminate\Database\Seeder;

/**
 * A demo partial vendor payment, mirroring Database\Seeders\Sales\PaymentSeeder
 * for the Purchases side, so a partial-payment scenario is visible on both
 * the receivables and payables sides out of the box.
 */
class VendorPaymentSeeder extends Seeder
{
    public function run(): void
    {
        if (VendorPayment::where('payment_number', 'like', 'VPAY-%')->exists()) {
            return;
        }

        $bill = Bill::where('bill_number', 'BILL-2026-0001')->where('status', 'posted')->first();
        $cash = Account::where('code', '1001')->first();

        if (! $bill || ! $cash || bccomp($bill->amountDue(), '0', 4) <= 0) {
            return;
        }

        $partialAmount = bcdiv($bill->total, '2', 4);

        app(MakePayment::class)->handle([
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => now()->subDay()->toDateString(),
            'reference' => 'Demo partial payment',
            'method' => 'Cash',
            'amount' => $partialAmount,
            'notes' => 'Demo: partial payment against '.$bill->bill_number,
            'created_by' => null,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => $partialAmount],
            ],
        ]);
    }
}
