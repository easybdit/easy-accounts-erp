<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Purchases\MakePayment;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StoreVendorPaymentRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use App\Models\Tax\WithholdingTaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class VendorPaymentController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $payments = VendorPayment::query()
            ->with('vendor:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VendorPayment $payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'vendor' => ['name' => $payment->vendor->name],
                'payment_date' => $payment->payment_date->toDateString(),
                'method' => $payment->method,
                'amount' => (string) $payment->amount,
            ]);

        return Inertia::render('Purchases/VendorPayments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/VendorPayments/Create', $this->formOptions());
    }

    public function store(StoreVendorPaymentRequest $request, MakePayment $action): RedirectResponse
    {
        try {
            $payment = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchases.vendor-payments.show', $payment)->with('success', 'Payment sent.');
    }

    public function show(VendorPayment $vendorPayment): Response
    {
        $vendorPayment->load(['vendor:id,name', 'paymentAccount:id,code,name', 'withholdingTaxRate:id,name,rate', 'allocations.bill:id,bill_number,total', 'journal']);

        $paymentData = $this->withPlainDates($vendorPayment, ['payment_date']);
        $paymentData['net_cash_paid'] = $vendorPayment->netCashPaid();

        return Inertia::render('Purchases/VendorPayments/Show', [
            'payment' => $paymentData,
        ]);
    }

    private function formOptions(): array
    {
        $openBills = Bill::query()
            ->where('status', 'posted')
            ->withSum('paymentAllocations as amount_paid', 'amount')
            ->get()
            ->map(fn (Bill $bill) => [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'vendor_id' => $bill->vendor_id,
                'total' => (string) $bill->total,
                'amount_due' => bcsub((string) $bill->total, (string) ($bill->amount_paid ?? '0.0000'), 4),
            ])
            ->filter(fn (array $bill) => bccomp($bill['amount_due'], '0', 4) > 0)
            ->values();

        return [
            'vendors' => Vendor::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'paymentAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'openBills' => $openBills,
            'withholdingTaxRates' => WithholdingTaxRate::query()->where('is_active', true)
                ->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
