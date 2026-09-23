<?php

namespace App\Http\Requests\Banking;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBankDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'deposit_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['integer', 'exists:payments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('bank_account_id'));

            if ($account && ! $account->is_bank_account) {
                $validator->errors()->add('bank_account_id', 'This must be a Cash/Bank account.');
            }

            if ($account && ! $account->is_active) {
                $validator->errors()->add('bank_account_id', 'This account is inactive.');
            }
        });
    }
}
