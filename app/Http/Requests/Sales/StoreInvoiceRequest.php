<?php

namespace App\Http\Requests\Sales;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'receivable_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'items.*.tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $receivableId = $this->input('receivable_account_id');

            if ($receivableId) {
                $receivable = Account::find($receivableId);

                if ($receivable && $receivable->type !== 'asset') {
                    $validator->errors()->add('receivable_account_id', 'The receivable account must be an asset account.');
                }

                if ($receivable && ! $receivable->is_active) {
                    $validator->errors()->add('receivable_account_id', 'The receivable account is inactive.');
                }
            }

            $itemAccountIds = collect($this->input('items', []))->pluck('account_id')->filter()->unique();
            $incomeAccounts = Account::whereIn('id', $itemAccountIds)->get()->keyBy('id');

            foreach ($this->input('items', []) as $index => $item) {
                $account = $incomeAccounts->get($item['account_id'] ?? null);

                if ($account && $account->type !== 'income') {
                    $validator->errors()->add("items.{$index}.account_id", 'Each invoice line must use an income account.');
                }

                if ($account && ! $account->is_active) {
                    $validator->errors()->add("items.{$index}.account_id", 'This account is inactive.');
                }

                $lineTotal = bcsub(
                    bcmul((string) ($item['quantity'] ?? 0), (string) ($item['unit_price'] ?? 0), 4),
                    (string) ($item['discount'] ?? 0),
                    4
                );

                if (bccomp($lineTotal, '0', 4) < 0) {
                    $validator->errors()->add("items.{$index}.discount", 'The discount cannot exceed the line amount.');
                }

                if (! empty($item['tax_rate_id'])) {
                    $taxRate = TaxRate::find($item['tax_rate_id']);

                    if ($taxRate && ! $taxRate->is_active) {
                        $validator->errors()->add("items.{$index}.tax_rate_id", 'This tax rate is inactive.');
                    }
                }
            }
        });
    }
}
