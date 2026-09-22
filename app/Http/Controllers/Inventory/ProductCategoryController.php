<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductCategoryRequest;
use App\Models\Inventory\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inventory/Categories/Index', [
            'categories' => ProductCategory::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Categories/Create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($request->validated());

        return redirect()->route('inventory.categories.index')->with('success', 'Category created.');
    }

    public function edit(ProductCategory $category): Response
    {
        return Inertia::render('Inventory/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(StoreProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('inventory.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'This category has products and cannot be deleted.']);
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')->with('success', 'Category deleted.');
    }
}
