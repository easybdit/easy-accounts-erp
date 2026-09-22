<?php

namespace App\Http\Requests\Tax;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taxRate = $this->route('rate');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tax_rates', 'name')->ignore($taxRate)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('tax_account_id'));

            if ($account && $account->type !== 'liability') {
                $validator->errors()->add('tax_account_id', 'The tax account must be a liability account.');
            }
        });
    }
}
