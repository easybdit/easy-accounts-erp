<?php

namespace Database\Seeders\Sales;

use App\Actions\Sales\ReceivePayment;
use App\Models\Accounting\Account;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use Illuminate\Database\Seeder;

/**
 * A demo partial payment against the seeded demo invoice, so "partial
 * payment" (Section 34) and the resulting paid/due split are visible and
 * verifiable out of the box, not just a hypothetical feature.
 */
class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        if (Payment::where('payment_number', 'like', 'PAY-%')->exists()) {
            return;
        }

        $invoice = Invoice::where('invoice_number', 'INV-2026-0001')->where('status', 'posted')->first();
        $bank = Account::where('code', '1002')->first();

        if (! $invoice || ! $bank || bccomp($invoice->amountDue(), '0', 4) <= 0) {
            return;
        }

        $partialAmount = bcdiv($invoice->total, '2', 4);

        app(ReceivePayment::class)->handle([
            'customer_id' => $invoice->customer_id,
            'deposit_account_id' => $bank->id,
            'payment_date' => now()->subDay()->toDateString(),
            'reference' => 'Demo partial payment',
            'method' => 'Bank Transfer',
            'amount' => $partialAmount,
            'notes' => 'Demo: partial payment against '.$invoice->invoice_number,
            'created_by' => null,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => $partialAmount],
            ],
        ]);
    }
}
