<?php

namespace App\Http\Requests\Tax;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWithholdingTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rate = $this->route('withholding_tax_rate');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('withholding_tax_rates', 'name')->ignore($rate)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'liability_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('liability_account_id'));

            if ($account && $account->type !== 'liability') {
                $validator->errors()->add('liability_account_id', 'The withholding account must be a liability account.');
            }
        });
    }
}
