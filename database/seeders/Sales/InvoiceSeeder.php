<?php

namespace Database\Seeders\Sales;

use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use Illuminate\Database\Seeder;

/**
 * A demo invoice exercised end to end (draft -> posted -> journal), so the
 * whole Sales pipeline is visible/testable out of the box, not just an
 * empty "Invoices" page.
 */
class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (Invoice::where('invoice_number', 'like', 'INV-%')->exists()) {
            return;
        }

        $customer = Customer::where('name', 'Rahman Enterprise')->first();
        $receivable = Account::where('code', '1003')->first();
        $serviceRevenue = Account::where('code', '4002')->first();

        if (! $customer || ! $receivable || ! $serviceRevenue) {
            return;
        }

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(27)->toDateString(),
            'notes' => 'Demo invoice seeded for verification purposes.',
            'items' => [
                [
                    'account_id' => $serviceRevenue->id,
                    'description' => 'Consulting services',
                    'quantity' => 10,
                    'unit_price' => 150,
                    'discount' => 0,
                ],
                [
                    'account_id' => $serviceRevenue->id,
                    'description' => 'Setup fee',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'discount' => 100,
                ],
            ],
        ]);

        app(PostInvoice::class)->handle($invoice);
    }
}
