<?php

namespace App\Http\Requests\Inventory;

use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $isInventory = $this->input('type') === 'inventory';

        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'type' => ['required', Rule::in(Product::TYPES)],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'income_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cogs_account_id' => [$isInventory ? 'required' : 'nullable', 'integer', 'exists:accounts,id'],
            'inventory_account_id' => [$isInventory ? 'required' : 'nullable', 'integer', 'exists:accounts,id'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'opening_quantity' => ['nullable', 'numeric'],
            'opening_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $income = Account::find($this->input('income_account_id'));
            if ($income && $income->type !== 'income') {
                $validator->errors()->add('income_account_id', 'This must be an income account.');
            }

            if ($this->input('type') === 'inventory') {
                $cogs = Account::find($this->input('cogs_account_id'));
                if ($cogs && $cogs->type !== 'expense') {
                    $validator->errors()->add('cogs_account_id', 'This must be an expense account.');
                }

                $inventory = Account::find($this->input('inventory_account_id'));
                if ($inventory && $inventory->type !== 'asset') {
                    $validator->errors()->add('inventory_account_id', 'This must be an asset account.');
                }
            }
        });
    }
}
