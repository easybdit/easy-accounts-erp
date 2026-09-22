<?php

namespace App\Http\Requests\Purchases;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBillRequest extends FormRequest
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
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
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

                if ($payable && ! $payable->is_active) {
                    $validator->errors()->add('payable_account_id', 'The payable account is inactive.');
                }
            }

            $itemAccountIds = collect($this->input('items', []))->pluck('account_id')->filter()->unique();
            $expenseAccounts = Account::whereIn('id', $itemAccountIds)->get()->keyBy('id');

            foreach ($this->input('items', []) as $index => $item) {
                $account = $expenseAccounts->get($item['account_id'] ?? null);

                if ($account && $account->type !== 'expense') {
                    $validator->errors()->add("items.{$index}.account_id", 'Each bill line must use an expense account.');
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
            }
        });
    }
}
