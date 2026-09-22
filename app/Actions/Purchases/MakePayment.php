<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Purchases-side mirror of App\Actions\Sales\ReceivePayment: paying a
 * vendor against one or more open bills. Creating it IS posting it — debit
 * each allocated bill's own payable account (tagged to the vendor), credit
 * the payment account (Cash/Bank) for the full amount. Every dollar paid
 * must be allocated to an open bill at creation time (same documented scope
 * limit as customer payments — no "on account" vendor payment yet).
 */
class MakePayment
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{vendor_id:int, payment_account_id:int, payment_date:string, reference:?string, method:?string, amount:numeric-string|float, notes:?string, created_by:?int, allocations: array<int, array{bill_id:int, amount:numeric-string|float}>}  $data
     */
    public function handle(array $data): VendorPayment
    {
        $amount = (string) $data['amount'];
        $allocations = $data['allocations'];

        $allocatedTotal = array_reduce(
            $allocations,
            fn (string $carry, array $allocation) => bcadd($carry, (string) $allocation['amount'], 4),
            '0.0000'
        );

        if (bccomp($allocatedTotal, $amount, 4) !== 0) {
            throw new RuntimeException(
                "Allocated total ({$allocatedTotal}) must equal the payment amount ({$amount})."
            );
        }

        $bills = Bill::whereIn('id', array_column($allocations, 'bill_id'))->get()->keyBy('id');

        foreach ($allocations as $allocation) {
            $bill = $bills->get($allocation['bill_id']);

            if (! $bill || $bill->vendor_id !== $data['vendor_id'] || $bill->status !== 'posted') {
                throw new RuntimeException('An allocation targets a bill that is not a posted bill for this vendor.');
            }

            if (bccomp((string) $allocation['amount'], $bill->amountDue(), 4) > 0) {
                throw new RuntimeException(
                    "Allocation of {$allocation['amount']} exceeds bill {$bill->bill_number}'s remaining due of {$bill->amountDue()}."
                );
            }
        }

        return DB::transaction(function () use ($data, $allocations, $bills, $amount) {
            $payment = VendorPayment::create([
                'payment_number' => $this->nextPaymentNumber($data['payment_date']),
                'vendor_id' => $data['vendor_id'],
                'payment_account_id' => $data['payment_account_id'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'method' => $data['method'] ?? null,
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $journalLines = [];

            foreach ($allocations as $allocation) {
                $bill = $bills->get($allocation['bill_id']);

                $payment->allocations()->create([
                    'bill_id' => $bill->id,
                    'amount' => $allocation['amount'],
                ]);

                $journalLines[] = [
                    'account_id' => $bill->payable_account_id,
                    'vendor_id' => $data['vendor_id'],
                    'debit' => (string) $allocation['amount'],
                    'credit' => 0,
                    'description' => "Applied to bill {$bill->bill_number}",
                ];
            }

            $journalLines[] = [
                'account_id' => $data['payment_account_id'],
                'debit' => 0,
                'credit' => $amount,
                'description' => "Payment {$payment->payment_number}",
            ];

            $this->postJournal->handle([
                'date' => $data['payment_date'],
                'reference' => $payment->payment_number,
                'description' => "Payment {$payment->payment_number} to vendor",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => VendorPayment::class,
                'source_id' => $payment->id,
                'lines' => $journalLines,
            ]);

            return $payment->load('allocations.bill', 'journal');
        });
    }

    private function nextPaymentNumber(string $paymentDate): string
    {
        $year = date('Y', strtotime($paymentDate));
        $count = VendorPayment::where('payment_number', 'like', "VPAY-{$year}-%")->count() + 1;

        return sprintf('VPAY-%s-%04d', $year, $count);
    }
}
