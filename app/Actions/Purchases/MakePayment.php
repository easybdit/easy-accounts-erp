<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use App\Models\Tax\WithholdingTaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Purchases-side mirror of App\Actions\Sales\ReceivePayment: paying a
 * vendor against one or more open bills. Creating it IS posting it — debit
 * each allocated bill's own payable account (tagged to the vendor) for the
 * full settled amount, credit the payment account (Cash/Bank) for the cash
 * actually disbursed. Every dollar settled must be allocated to an open
 * bill at creation time (same documented scope limit as customer payments
 * — no "on account" vendor payment yet).
 *
 * Optional TDS/VDS withholding (Bangladesh: Tax Deducted at Source / VAT
 * Deducted at Source): a WithholdingTaxRate applied here doesn't reduce
 * what the vendor is credited for — the bill's payable is still cleared in
 * full — it just splits where the cash goes: part to the vendor (Cash/
 * Bank), part redirected to a liability owed to the tax authority instead
 * (App\Models\Tax\WithholdingTaxRate::liability_account_id). Depositing
 * that liability to the government is a separate, periodic transaction,
 * recorded as an ordinary manual Journal entry — not modeled here, since
 * it isn't per-vendor-payment.
 */
class MakePayment
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{vendor_id:int, payment_account_id:int, payment_date:string, reference:?string, method:?string, amount:numeric-string|float, withholding_tax_rate_id?:?int, notes:?string, created_by:?int, allocations: array<int, array{bill_id:int, amount:numeric-string|float}>}  $data
     */
    public function handle(array $data): VendorPayment
    {
        $amount = (string) $data['amount'];
        $allocations = $data['allocations'];

        $withholdingTaxRate = ! empty($data['withholding_tax_rate_id'])
            ? WithholdingTaxRate::findOrFail($data['withholding_tax_rate_id'])
            : null;
        $withholdingTaxAmount = $withholdingTaxRate ? $withholdingTaxRate->calculate($amount) : '0.0000';

        if (bccomp($withholdingTaxAmount, $amount, 4) > 0) {
            throw new RuntimeException('The withheld amount cannot exceed the payment amount.');
        }

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

        return DB::transaction(function () use ($data, $allocations, $bills, $amount, $withholdingTaxRate, $withholdingTaxAmount) {
            $payment = VendorPayment::create([
                'payment_number' => $this->nextPaymentNumber($data['payment_date']),
                'vendor_id' => $data['vendor_id'],
                'payment_account_id' => $data['payment_account_id'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'method' => $data['method'] ?? null,
                'amount' => $amount,
                'withholding_tax_rate_id' => $withholdingTaxRate?->id,
                'withholding_tax_amount' => $withholdingTaxAmount,
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

            $netCashPaid = bcsub($amount, $withholdingTaxAmount, 4);

            if (bccomp($netCashPaid, '0', 4) > 0) {
                $journalLines[] = [
                    'account_id' => $data['payment_account_id'],
                    'debit' => 0,
                    'credit' => $netCashPaid,
                    'description' => "Payment {$payment->payment_number}",
                ];
            }

            if (bccomp($withholdingTaxAmount, '0', 4) > 0) {
                $journalLines[] = [
                    'account_id' => $withholdingTaxRate->liability_account_id,
                    'vendor_id' => $data['vendor_id'],
                    'debit' => 0,
                    'credit' => $withholdingTaxAmount,
                    'description' => "{$withholdingTaxRate->name} withheld — Payment {$payment->payment_number}",
                ];
            }

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
