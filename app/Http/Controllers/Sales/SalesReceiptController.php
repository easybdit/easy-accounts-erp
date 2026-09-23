<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\RecordSalesReceipt;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesReceiptRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Inventory\Product;
use App\Models\Sales\SalesReceipt;
use App\Models\Tax\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SalesReceiptController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $receipts = SalesReceipt::query()
            ->with('customer:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SalesReceipt $receipt) => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'customer' => ['id' => $receipt->customer->id, 'name' => $receipt->customer->name],
                'receipt_date' => $receipt->receipt_date->toDateString(),
                'total' => (string) $receipt->total,
            ]);

        return Inertia::render('Sales/SalesReceipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/SalesReceipts/Create', $this->formOptions());
    }

    public function store(StoreSalesReceiptRequest $request, RecordSalesReceipt $action): RedirectResponse
    {
        try {
            $receipt = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('sales.sales-receipts.show', $receipt)->with('success', 'Sales receipt recorded.');
    }

    public function show(SalesReceipt $salesReceipt): Response
    {
        $salesReceipt->load([
            'customer:id,name,email',
            'depositAccount:id,code,name',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'items.taxRate2:id,name,rate',
            'journal',
        ]);

        return Inertia::render('Sales/SalesReceipts/Show', [
            'receipt' => $this->withPlainDates($salesReceipt, ['receipt_date']),
        ]);
    }

    public function pdf(SalesReceipt $salesReceipt): HttpResponse
    {
        $salesReceipt->load(['customer', 'items.account', 'items.taxRate', 'items.taxRate2']);

        $pdf = Pdf::loadView('pdfs.sales-receipt', [
            'receipt' => $salesReceipt,
            'appName' => config('app.name'),
        ]);

        return $pdf->download("{$salesReceipt->receipt_number}.pdf");
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'depositAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)
                ->select('id', 'sku', 'name', 'type', 'selling_price', 'income_account_id')
                ->orderBy('name')->get(),
        ];
    }
}
