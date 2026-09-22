<?php

namespace App\Http\Requests\Purchases;

use App\Models\Accounting\Account;
use App\Models\Purchases\Bill;
use App\Models\Tax\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVendorCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'payable_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'bill_id' => ['nullable', 'integer', 'exists:bills,id'],
            'vendor_credit_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
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
            $payableId = $this->input('payable_account_id');

            if ($payableId) {
                $payable = Account::find($payableId);

                if ($payable && $payable->type !== 'liability') {
                    $validator->errors()->add('payable_account_id', 'The payable account must be a liability account.');
                }
            }

            if ($this->filled('bill_id')) {
                $bill = Bill::find($this->input('bill_id'));

                if ($bill && $bill->vendor_id !== (int) $this->input('vendor_id')) {
                    $validator->errors()->add('bill_id', 'The selected bill does not belong to this vendor.');
                }
            }

            $itemAccountIds = collect($this->input('items', []))->pluck('account_id')->filter()->unique();
            $expenseAccounts = Account::whereIn('id', $itemAccountIds)->get()->keyBy('id');

            foreach ($this->input('items', []) as $index => $item) {
                $account = $expenseAccounts->get($item['account_id'] ?? null);

                if ($account && $account->type !== 'expense') {
                    $validator->errors()->add("items.{$index}.account_id", 'Each line must use an expense account.');
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
