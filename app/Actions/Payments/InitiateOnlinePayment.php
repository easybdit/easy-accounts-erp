<?php

namespace App\Actions\Payments;

use App\Models\Sales\InvoicePaymentLink;
use App\Models\Sales\OnlinePaymentTransaction;
use App\Services\SslCommerz\SslCommerzClient;
use Illuminate\Support\Str;
use RuntimeException;

class InitiateOnlinePayment
{
    public function __construct(private SslCommerzClient $client) {}

    /**
     * Opens an SSLCommerz session for the link's invoice and returns the
     * GatewayPageURL to redirect the customer's browser to.
     */
    public function handle(InvoicePaymentLink $link): string
    {
        if (! config('services.sslcommerz.enabled')) {
            throw new RuntimeException('Online payments are currently disabled. Please contact the business to arrange payment another way.');
        }

        if (! $link->is_active) {
            throw new RuntimeException('This payment link is no longer active.');
        }

        $invoice = $link->invoice;
        $amountDue = $invoice->amountDue();

        if (bccomp($amountDue, '0', 4) <= 0) {
            throw new RuntimeException('This invoice is already fully paid.');
        }

        $invoice->loadMissing('customer');

        $transaction = OnlinePaymentTransaction::create([
            'invoice_payment_link_id' => $link->id,
            'tran_id' => 'INV'.$invoice->id.'-'.Str::random(16),
            'amount' => $amountDue,
            'currency' => config('services.sslcommerz.currency'),
            'status' => 'initiated',
        ]);

        $response = $this->client->initiateSession([
            'total_amount' => $amountDue,
            'currency' => $transaction->currency,
            'tran_id' => $transaction->tran_id,
            'success_url' => route('pay.callback.success'),
            'fail_url' => route('pay.callback.fail'),
            'cancel_url' => route('pay.callback.cancel'),
            'ipn_url' => route('pay.callback.ipn'),
            'cus_name' => $invoice->customer->name,
            'cus_email' => $invoice->customer->email ?: 'no-reply@example.com',
            'cus_add1' => $invoice->customer->address ?: 'N/A',
            'cus_city' => 'N/A',
            'cus_postcode' => '0000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $invoice->customer->phone ?: '00000000000',
            'shipping_method' => 'NO',
            'product_name' => "Invoice {$invoice->invoice_number}",
            'product_category' => 'Service',
            'product_profile' => 'general',
            'num_of_item' => 1,
        ]);

        $transaction->update(['gateway_response' => $response]);

        if (($response['status'] ?? null) !== 'SUCCESS' || empty($response['GatewayPageURL'])) {
            $transaction->update(['status' => 'failed']);

            throw new RuntimeException('Could not start the payment session. Please try again shortly.');
        }

        return $response['GatewayPageURL'];
    }
}
