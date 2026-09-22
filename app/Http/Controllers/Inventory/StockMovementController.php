<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class StockMovementController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $movements = StockMovement::query()
            ->with('product:id,sku,name')
            ->when($request->integer('product_id'), fn ($query, $productId) => $query->where('product_id', $productId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'date' => $movement->date->toDateString(),
                'product' => ['sku' => $movement->product->sku, 'name' => $movement->product->name],
                'reason' => $movement->reason,
                'quantity' => (string) $movement->quantity,
            ]);

        return Inertia::render('Inventory/StockMovements/Index', [
            'movements' => $movements,
            'filters' => $request->only(['product_id']),
            'products' => Product::query()->where('type', 'inventory')->select('id', 'sku', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/StockMovements/Create', [
            'products' => Product::query()->where('type', 'inventory')->where('is_active', true)
                ->get(['id', 'sku', 'name'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'current_stock' => $product->currentStock(),
                ]),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, AdjustStock $action): RedirectResponse
    {
        try {
            $movement = $action->handle([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['adjustment' => $e->getMessage()])->withInput();
        }

        return redirect()->route('inventory.stock-movements.show', $movement)->with('success', 'Stock adjusted.');
    }

    public function show(StockMovement $stockMovement): Response
    {
        $stockMovement->load(['product:id,sku,name', 'journal']);

        return Inertia::render('Inventory/StockMovements/Show', [
            'movement' => $this->withPlainDates($stockMovement, ['date']),
        ]);
    }
}
