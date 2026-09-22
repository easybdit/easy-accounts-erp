<?php

namespace App\Actions\Payments;

use App\Actions\Sales\ReceivePayment;
use App\Models\Sales\OnlinePaymentTransaction;
use App\Services\SslCommerz\SslCommerzClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Shared by both the success_url redirect and the ipn_url webhook (Section
 * 90) — SSLCommerz calls both, and either can arrive first or not at all,
 * so this must be safe to run twice for the same transaction. The
 * success_url is only ever a browser navigation from an external site and
 * can be reached without ever paying, so this always re-validates
 * server-to-server via SslCommerzClient rather than trusting whatever
 * fields arrived in the callback body.
 */
class ProcessOnlinePaymentCallback
{
    public function __construct(
        private SslCommerzClient $client,
        private ReceivePayment $receivePayment,
    ) {}

    public function handle(?string $tranId, ?string $valId): OnlinePaymentTransaction
    {
        $transaction = OnlinePaymentTransaction::where('tran_id', $tranId)->firstOrFail();

        if ($transaction->isValidated()) {
            return $transaction;
        }

        if (! $valId) {
            $transaction->update(['status' => 'failed']);

            return $transaction;
        }

        $validation = $this->client->validateTransaction($valId);

        $statusOk = in_array($validation['status'] ?? null, ['VALID', 'VALIDATED'], true);
        // 2-decimal comparison: SSLCommerz's validation API returns amounts
        // formatted to 2 decimals regardless of this app's 4-decimal money
        // precision.
        $amountOk = $statusOk && bccomp((string) ($validation['amount'] ?? '0'), (string) $transaction->amount, 2) === 0;
        $currencyOk = $statusOk && ($validation['currency_type'] ?? null) === $transaction->currency;

        if (! $statusOk || ! $amountOk || ! $currencyOk) {
            $transaction->update(['status' => 'failed', 'val_id' => $valId, 'gateway_response' => $validation]);

            return $transaction;
        }

        return DB::transaction(function () use ($transaction, $valId, $validation) {
            $link = $transaction->invoicePaymentLink;
            $invoice = $link->invoice;

            try {
                $payment = $this->receivePayment->handle([
                    'customer_id' => $invoice->customer_id,
                    'deposit_account_id' => $link->deposit_account_id,
                    'payment_date' => now()->toDateString(),
                    'reference' => $transaction->tran_id,
                    'method' => 'Online (SSLCommerz)',
                    'amount' => $transaction->amount,
                    'notes' => 'Paid online via SSLCommerz payment link',
                    'created_by' => null,
                    'allocations' => [['invoice_id' => $invoice->id, 'amount' => $transaction->amount]],
                ]);
            } catch (RuntimeException $e) {
                // The invoice's amount due changed between link generation
                // and payment (e.g. a manual payment was recorded in the
                // meantime) — the money was validated as received by
                // SSLCommerz, but can't be safely auto-allocated. Leave the
                // transaction validated-but-unlinked for staff to reconcile
                // manually rather than silently losing the record.
                $transaction->update(['status' => 'validated', 'val_id' => $valId, 'gateway_response' => $validation]);

                return $transaction;
            }

            $transaction->update([
                'status' => 'validated',
                'val_id' => $valId,
                'gateway_response' => $validation,
                'payment_id' => $payment->id,
            ]);

            return $transaction;
        });
    }
}
