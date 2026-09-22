<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Account::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
            'is_bank_account' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');
            $type = $this->input('type');

            if ($parentId && $type) {
                $parentType = Account::where('id', $parentId)->value('type');

                if ($parentType !== null && $parentType !== $type) {
                    $validator->errors()->add(
                        'parent_id',
                        'The selected parent account must have the same account type.'
                    );
                }
            }

            if ($this->boolean('is_bank_account') && $type !== 'asset') {
                $validator->errors()->add('is_bank_account', 'Only an asset account can be marked as a bank/cash account.');
            }
        });
    }
}
