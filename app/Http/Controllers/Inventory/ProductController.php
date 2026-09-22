<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\CreateProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name,
                'type' => $product->type,
                'unit' => $product->unit,
                'selling_price' => (string) $product->selling_price,
                'current_stock' => $product->isInventoryTracked() ? $product->currentStock() : null,
                'is_low_stock' => $product->isLowStock(),
                'is_active' => $product->is_active,
            ]);

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Products/Create', $this->formOptions());
    }

    public function store(StoreProductRequest $request, CreateProduct $action): RedirectResponse
    {
        $product = $action->handle($request->validated());

        return redirect()->route('inventory.products.show', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Inventory/Products/Edit', [
            'product' => $product,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->safe()->except(['opening_quantity', 'opening_date']));

        return redirect()->route('inventory.products.show', $product)->with('success', 'Product updated.');
    }

    public function show(Product $product): Response
    {
        $product->load(['category:id,name', 'incomeAccount:id,code,name', 'cogsAccount:id,code,name', 'inventoryAccount:id,code,name']);

        $movements = $product->stockMovements()->orderBy('date')->orderBy('id')->get();
        $running = '0.0000';

        $rows = $movements->map(function ($movement) use (&$running) {
            $running = bcadd($running, (string) $movement->quantity, 4);

            return [
                'id' => $movement->id,
                'date' => $movement->date->toDateString(),
                'quantity' => (string) $movement->quantity,
                'reason' => $movement->reason,
                'reference' => $movement->reference,
                'running_balance' => $running,
            ];
        });

        return Inertia::render('Inventory/Products/Show', [
            'product' => $product,
            'currentStock' => $product->isInventoryTracked() ? $product->currentStock() : null,
            'stockValue' => $product->isInventoryTracked() ? $product->stockValue() : null,
            'isLowStock' => $product->isLowStock(),
            'movements' => $rows,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stockMovements()->exists()) {
            return back()->with('error', 'This product has stock movement history and cannot be deleted.');
        }

        $product->delete();

        return redirect()->route('inventory.products.index')->with('success', 'Product deleted.');
    }

    private function formOptions(): array
    {
        return [
            'categories' => ProductCategory::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'assetAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
