<?php

namespace App\Http\Requests\Expenses;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRecurringExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('account_id'));

            if ($account && $account->type !== 'expense') {
                $validator->errors()->add('account_id', 'The expense account must be an expense-type account.');
            }
            if ($account && ! $account->is_active) {
                $validator->errors()->add('account_id', 'The expense account is inactive.');
            }

            $paymentAccount = Account::find($this->input('payment_account_id'));

            if ($paymentAccount && $paymentAccount->type !== 'asset') {
                $validator->errors()->add('payment_account_id', 'The payment account must be an asset account.');
            }
            if ($paymentAccount && ! $paymentAccount->is_active) {
                $validator->errors()->add('payment_account_id', 'The payment account is inactive.');
            }

            if ($this->filled('tax_rate_id')) {
                $taxRate = TaxRate::find($this->input('tax_rate_id'));

                if ($taxRate && ! $taxRate->is_active) {
                    $validator->errors()->add('tax_rate_id', 'This tax rate is inactive.');
                }
            }
        });
    }
}
