<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\InitiateOnlinePayment;
use App\Actions\Payments\ProcessOnlinePaymentCallback;
use App\Http\Controllers\Controller;
use App\Models\Sales\InvoicePaymentLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Entirely unauthenticated (Section 90) — reached only via a token-bearing
 * link a staff member generated and sent to the customer directly, plus
 * SSLCommerz's own server-to-server/browser callbacks. Never render or
 * return anything beyond what a link recipient should see (invoice number,
 * customer name, amount due — never other financial detail).
 */
class PublicPaymentController extends Controller
{
    public function show(string $token): Response
    {
        $link = InvoicePaymentLink::where('token', $token)->where('is_active', true)->firstOrFail();
        $invoice = $link->invoice()->with('customer:id,name')->firstOrFail();

        return Inertia::render('Public/Pay/Show', [
            'token' => $token,
            'invoiceNumber' => $invoice->invoice_number,
            'customerName' => $invoice->customer->name,
            'amountDue' => $invoice->amountDue(),
            'currency' => config('services.sslcommerz.currency'),
            'alreadyPaid' => bccomp($invoice->amountDue(), '0', 4) <= 0,
        ]);
    }

    public function initiate(string $token, InitiateOnlinePayment $action): RedirectResponse
    {
        $link = InvoicePaymentLink::where('token', $token)->where('is_active', true)->firstOrFail();

        try {
            $gatewayUrl = $action->handle($link);
        } catch (RuntimeException $e) {
            return redirect()->route('pay.show', $token)->with('error', $e->getMessage());
        }

        return redirect()->away($gatewayUrl);
    }

    public function success(Request $request, ProcessOnlinePaymentCallback $action): Response
    {
        $transaction = $action->handle($request->input('tran_id'), $request->input('val_id'));

        return Inertia::render('Public/Pay/Result', [
            'outcome' => $transaction->isValidated() ? 'success' : 'failed',
        ]);
    }

    public function fail(Request $request): Response
    {
        return Inertia::render('Public/Pay/Result', ['outcome' => 'failed']);
    }

    public function cancel(Request $request): Response
    {
        return Inertia::render('Public/Pay/Result', ['outcome' => 'cancelled']);
    }

    /**
     * Server-to-server webhook — SSLCommerz does not read the response
     * body, just the status code, so this always returns a plain 200.
     */
    public function ipn(Request $request, ProcessOnlinePaymentCallback $action): HttpResponse
    {
        $action->handle($request->input('tran_id'), $request->input('val_id'));

        return response('OK', 200);
    }
}
