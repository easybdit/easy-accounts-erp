<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $account = $this->route('account');

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('accounts', 'code')->ignore($account)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Account::TYPES)],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
                Rule::notIn([$account?->id]),
            ],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
            'is_bank_account' => ['boolean'],
            'cash_flow_category' => ['nullable', Rule::in(Account::CASH_FLOW_CATEGORIES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = $this->route('account');
            $parentId = $this->input('parent_id');
            $type = $this->input('type');

            if ($this->boolean('is_bank_account') && $type !== 'asset') {
                $validator->errors()->add('is_bank_account', 'Only an asset account can be marked as a bank/cash account.');
            }

            if (! $parentId) {
                return;
            }

            if ($type) {
                $parentType = Account::where('id', $parentId)->value('type');

                if ($parentType !== null && $parentType !== $type) {
                    $validator->errors()->add(
                        'parent_id',
                        'The selected parent account must have the same account type.'
                    );
                }
            }

            if ($account && in_array((int) $parentId, $account->descendantIds(), true)) {
                $validator->errors()->add(
                    'parent_id',
                    'The selected parent account cannot be a descendant of this account.'
                );
            }
        });
    }
}
