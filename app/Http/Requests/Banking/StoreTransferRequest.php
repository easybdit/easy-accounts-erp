<?php

namespace App\Http\Requests\Banking;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:to_account_id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'transfer_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (['from_account_id', 'to_account_id'] as $field) {
                $account = Account::find($this->input($field));

                if ($account && $account->type !== 'asset') {
                    $validator->errors()->add($field, 'This must be an asset account (Cash/Bank).');
                }

                if ($account && ! $account->is_active) {
                    $validator->errors()->add($field, 'This account is inactive.');
                }
            }
        });
    }
}
