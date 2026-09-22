<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Purchases\PostBill;
use App\Actions\Purchases\SaveBillDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StoreBillRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BillController extends Controller
{
    public function index(Request $request): Response
    {
        $bills = Bill::query()
            ->with('vendor:id,name')
            ->withSum('paymentAllocations as amount_paid', 'amount')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('bill_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('bill_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (Bill $bill) {
                $amountPaid = (string) ($bill->amount_paid ?? '0.0000');

                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'vendor' => ['id' => $bill->vendor->id, 'name' => $bill->vendor->name],
                    'bill_date' => $bill->bill_date->toDateString(),
                    'total' => (string) $bill->total,
                    'amount_paid' => $amountPaid,
                    'amount_due' => bcsub((string) $bill->total, $amountPaid, 4),
                    'status' => $bill->status,
                ];
            });

        return Inertia::render('Purchases/Bills/Index', [
            'bills' => $bills,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/Bills/Create', $this->formOptions());
    }

    public function store(StoreBillRequest $request, SaveBillDraft $action): RedirectResponse
    {
        $bill = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('purchases.bills.show', $bill)->with('success', 'Bill saved as draft.');
    }

    public function edit(Bill $bill): Response
    {
        abort_unless($bill->isDraft(), 403, 'A posted bill cannot be edited.');

        return Inertia::render('Purchases/Bills/Edit', [
            'bill' => $bill->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreBillRequest $request, Bill $bill, SaveBillDraft $action): RedirectResponse
    {
        abort_unless($bill->isDraft(), 403, 'A posted bill cannot be edited.');

        $action->handle($request->validated(), $bill);

        return redirect()->route('purchases.bills.show', $bill)->with('success', 'Bill updated.');
    }

    public function show(Bill $bill): Response
    {
        $bill->load([
            'vendor:id,name',
            'payableAccount:id,code,name',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'journal',
            'paymentAllocations.vendorPayment:id,payment_number,payment_date',
        ]);

        return Inertia::render('Purchases/Bills/Show', [
            'bill' => $bill,
            'amountPaid' => $bill->amountPaid(),
            'amountDue' => $bill->amountDue(),
        ]);
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        abort_unless($bill->isDraft(), 403, 'A posted bill cannot be deleted.');

        $bill->delete();

        return redirect()->route('purchases.bills.index')->with('success', 'Bill deleted.');
    }

    public function post(Bill $bill, PostBill $action): RedirectResponse
    {
        try {
            $action->handle($bill);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bill' => $e->getMessage()]);
        }

        return redirect()->route('purchases.bills.show', $bill)->with('success', 'Bill posted.');
    }

    private function formOptions(): array
    {
        return [
            'vendors' => Vendor::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'payableAccounts' => Account::query()->where('is_active', true)->where('type', 'liability')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
