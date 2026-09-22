<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Purchases\PostVendorCredit;
use App\Actions\Purchases\SaveVendorCreditDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StoreVendorCreditRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\VendorCredit;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class VendorCreditController extends Controller
{
    public function index(Request $request): Response
    {
        $vendorCredits = VendorCredit::query()
            ->with('vendor:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('vendor_credit_number', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('vendor_credit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (VendorCredit $vendorCredit) => [
                'id' => $vendorCredit->id,
                'vendor_credit_number' => $vendorCredit->vendor_credit_number,
                'vendor' => ['id' => $vendorCredit->vendor->id, 'name' => $vendorCredit->vendor->name],
                'vendor_credit_date' => $vendorCredit->vendor_credit_date->toDateString(),
                'total' => (string) $vendorCredit->total,
                'status' => $vendorCredit->status,
            ]);

        return Inertia::render('Purchases/VendorCredits/Index', [
            'vendorCredits' => $vendorCredits,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/VendorCredits/Create', $this->formOptions());
    }

    public function store(StoreVendorCreditRequest $request, SaveVendorCreditDraft $action): RedirectResponse
    {
        $vendorCredit = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('purchases.vendor-credits.show', $vendorCredit)->with('success', 'Vendor credit saved as draft.');
    }

    public function edit(VendorCredit $vendorCredit): Response
    {
        abort_unless($vendorCredit->isDraft(), 403, 'A posted vendor credit cannot be edited.');

        return Inertia::render('Purchases/VendorCredits/Edit', [
            'vendorCredit' => $vendorCredit->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreVendorCreditRequest $request, VendorCredit $vendorCredit, SaveVendorCreditDraft $action): RedirectResponse
    {
        abort_unless($vendorCredit->isDraft(), 403, 'A posted vendor credit cannot be edited.');

        $action->handle($request->validated(), $vendorCredit);

        return redirect()->route('purchases.vendor-credits.show', $vendorCredit)->with('success', 'Vendor credit updated.');
    }

    public function show(VendorCredit $vendorCredit): Response
    {
        $vendorCredit->load([
            'vendor:id,name',
            'payableAccount:id,code,name',
            'bill:id,bill_number',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'journal',
        ]);

        return Inertia::render('Purchases/VendorCredits/Show', [
            'vendorCredit' => $vendorCredit,
        ]);
    }

    public function destroy(VendorCredit $vendorCredit): RedirectResponse
    {
        abort_unless($vendorCredit->isDraft(), 403, 'A posted vendor credit cannot be deleted.');

        $vendorCredit->delete();

        return redirect()->route('purchases.vendor-credits.index')->with('success', 'Vendor credit deleted.');
    }

    public function post(VendorCredit $vendorCredit, PostVendorCredit $action): RedirectResponse
    {
        try {
            $action->handle($vendorCredit);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.vendor-credits.show', $vendorCredit)->with('success', 'Vendor credit posted.');
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
