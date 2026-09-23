<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\ReceivePayment;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePaymentRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PaymentController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with('customer:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'customer' => ['name' => $payment->customer->name],
                'payment_date' => $payment->payment_date->toDateString(),
                'method' => $payment->method,
                'amount' => (string) $payment->amount,
            ]);

        return Inertia::render('Sales/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Payments/Create', $this->formOptions());
    }

    public function store(StorePaymentRequest $request, ReceivePayment $action): RedirectResponse
    {
        try {
            $payment = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()])->withInput();
        }

        return redirect()->route('sales.payments.show', $payment)->with('success', 'Payment received.');
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['customer:id,name', 'depositAccount:id,code,name,is_undeposited_funds', 'allocations.invoice:id,invoice_number,total', 'journal', 'bankDeposit:id,deposit_number']);

        return Inertia::render('Sales/Payments/Show', [
            'payment' => $this->withPlainDates($payment, ['payment_date']),
        ]);
    }

    private function formOptions(): array
    {
        $openInvoices = Invoice::query()
            ->where('status', 'posted')
            ->withSum('paymentAllocations as amount_paid', 'amount')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id,
                'total' => (string) $invoice->total,
                'amount_due' => bcsub((string) $invoice->total, (string) ($invoice->amount_paid ?? '0.0000'), 4),
            ])
            ->filter(fn (array $invoice) => bccomp($invoice['amount_due'], '0', 4) > 0)
            ->values();

        return [
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'depositAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'openInvoices' => $openInvoices,
        ];
    }
}
