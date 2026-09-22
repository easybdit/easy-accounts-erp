<?php

namespace Database\Seeders\Tax;

use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Tax\TaxRate;
use Illuminate\Database\Seeder;

/**
 * A demo taxed invoice, separate from Database\Seeders\Sales\InvoiceSeeder's
 * (untaxed) demo invoice, so both the with-tax and without-tax paths are
 * visible and verifiable out of the box.
 */
class TaxDemoInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::where('name', 'Green Valley Ltd')->first();
        $receivable = Account::where('code', '1003')->first();
        $serviceRevenue = Account::where('code', '4002')->first();
        $taxRate = TaxRate::where('name', 'VAT 15%')->first();

        if (! $customer || ! $receivable || ! $serviceRevenue || ! $taxRate) {
            return;
        }

        if (Invoice::where('customer_id', $customer->id)->where('notes', 'like', 'Demo taxed invoice%')->exists()) {
            return;
        }

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDay()->toDateString(),
            'notes' => 'Demo taxed invoice seeded for verification purposes.',
            'items' => [
                [
                    'account_id' => $serviceRevenue->id,
                    'tax_rate_id' => $taxRate->id,
                    'description' => 'Design services',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount' => 0,
                ],
            ],
        ]);

        app(PostInvoice::class)->handle($invoice);
    }
}
