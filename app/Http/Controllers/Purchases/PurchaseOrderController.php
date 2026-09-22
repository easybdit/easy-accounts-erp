<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Purchases\ConvertPurchaseOrderToBill;
use App\Actions\Purchases\SavePurchaseOrderDraft;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StorePurchaseOrderRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PurchaseOrderController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with('vendor:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PurchaseOrder $po) => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'vendor' => ['id' => $po->vendor->id, 'name' => $po->vendor->name],
                'order_date' => $po->order_date->toDateString(),
                'total' => (string) $po->total,
                'status' => $po->status,
            ]);

        return Inertia::render('Purchases/PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/PurchaseOrders/Create', $this->formOptions());
    }

    public function store(StorePurchaseOrderRequest $request, SavePurchaseOrderDraft $action): RedirectResponse
    {
        $purchaseOrder = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('purchases.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order saved.');
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($purchaseOrder->isEditable(), 403, 'A converted purchase order cannot be edited.');

        return Inertia::render('Purchases/PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StorePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, SavePurchaseOrderDraft $action): RedirectResponse
    {
        abort_unless($purchaseOrder->isEditable(), 403, 'A converted purchase order cannot be edited.');

        $action->handle($request->validated(), $purchaseOrder);

        return redirect()->route('purchases.purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'vendor:id,name',
            'payableAccount:id,code,name',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'convertedBill:id,bill_number,status',
        ]);

        return Inertia::render('Purchases/PurchaseOrders/Show', [
            'purchaseOrder' => $this->withPlainDates($purchaseOrder, ['order_date', 'expected_date']),
        ]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->isEditable(), 403, 'A converted purchase order cannot be deleted.');

        $purchaseOrder->delete();

        return redirect()->route('purchases.purchase-orders.index')->with('success', 'Purchase order deleted.');
    }

    public function convert(PurchaseOrder $purchaseOrder, ConvertPurchaseOrderToBill $action): RedirectResponse
    {
        try {
            $bill = $action->handle($purchaseOrder, request()->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.bills.edit', $bill)
            ->with('success', "Draft bill {$bill->bill_number} created from {$purchaseOrder->po_number} — review before posting.");
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
