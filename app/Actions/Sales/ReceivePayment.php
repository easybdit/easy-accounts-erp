<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\GenerateDocumentNumber;
use App\Actions\Accounting\PostJournal;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records a customer payment and, in the same atomic step, posts it:
 * debit the deposit account (Cash/Bank) for the full amount, credit each
 * allocated invoice's own receivable account (tagged to the customer) for
 * the allocated amount. Every dollar received must be allocated to an open
 * invoice at creation time — there is no "unapplied payment" concept yet,
 * since that GL treatment is a business-policy decision Section 20/84 says
 * must be confirmed, not guessed.
 */
class ReceivePayment
{
    public function __construct(private PostJournal $postJournal, private GenerateDocumentNumber $generateDocumentNumber) {}

    /**
     * @param  array{customer_id:int, deposit_account_id:int, payment_date:string, reference:?string, method:?string, amount:numeric-string|float, notes:?string, created_by:?int, allocations: array<int, array{invoice_id:int, amount:numeric-string|float}>}  $data
     */
    public function handle(array $data): Payment
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

        $invoices = Invoice::whereIn('id', array_column($allocations, 'invoice_id'))->get()->keyBy('id');

        foreach ($allocations as $allocation) {
            $invoice = $invoices->get($allocation['invoice_id']);

            if (! $invoice || $invoice->customer_id !== $data['customer_id'] || $invoice->status !== 'posted') {
                throw new RuntimeException('An allocation targets an invoice that is not a posted invoice for this customer.');
            }

            if (bccomp((string) $allocation['amount'], $invoice->amountDue(), 4) > 0) {
                throw new RuntimeException(
                    "Allocation of {$allocation['amount']} exceeds invoice {$invoice->invoice_number}'s remaining due of {$invoice->amountDue()}."
                );
            }
        }

        return DB::transaction(function () use ($data, $allocations, $invoices, $amount) {
            $payment = Payment::create([
                'payment_number' => $this->generateDocumentNumber->handle('payment', Payment::class, 'payment_number', $data['payment_date']),
                'customer_id' => $data['customer_id'],
                'deposit_account_id' => $data['deposit_account_id'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'method' => $data['method'] ?? null,
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $journalLines = [
                [
                    'account_id' => $data['deposit_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Payment {$payment->payment_number}",
                ],
            ];

            foreach ($allocations as $allocation) {
                $invoice = $invoices->get($allocation['invoice_id']);

                $payment->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => $allocation['amount'],
                ]);

                $journalLines[] = [
                    'account_id' => $invoice->receivable_account_id,
                    'customer_id' => $data['customer_id'],
                    'debit' => 0,
                    'credit' => (string) $allocation['amount'],
                    'description' => "Applied to invoice {$invoice->invoice_number}",
                ];
            }

            $this->postJournal->handle([
                'date' => $data['payment_date'],
                'reference' => $payment->payment_number,
                'description' => "Payment {$payment->payment_number} received",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'lines' => $journalLines,
            ]);

            return $payment->load('allocations.invoice', 'journal');
        });
    }
}
