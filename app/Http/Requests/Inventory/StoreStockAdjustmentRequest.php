<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $product = Product::find($this->input('product_id'));

            if ($product && ! $product->isInventoryTracked()) {
                $validator->errors()->add('product_id', 'Only inventory-tracked products can have their stock adjusted.');
            }
        });
    }
}
