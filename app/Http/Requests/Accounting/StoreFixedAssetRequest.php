<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:asset_account_id'],
            'depreciation_expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $assetAccount = Account::find($this->input('asset_account_id'));
            if ($assetAccount && $assetAccount->type !== 'asset') {
                $validator->errors()->add('asset_account_id', 'The asset account must be an asset-type account.');
            }

            $accumulatedAccount = Account::find($this->input('accumulated_depreciation_account_id'));
            if ($accumulatedAccount && $accumulatedAccount->type !== 'asset') {
                $validator->errors()->add('accumulated_depreciation_account_id', 'The accumulated depreciation account must be an asset-type account.');
            }

            $expenseAccount = Account::find($this->input('depreciation_expense_account_id'));
            if ($expenseAccount && $expenseAccount->type !== 'expense') {
                $validator->errors()->add('depreciation_expense_account_id', 'The depreciation expense account must be an expense-type account.');
            }

            $cost = (string) $this->input('purchase_cost', 0);
            $salvage = (string) $this->input('salvage_value', 0);
            if (bccomp($cost, $salvage, 4) <= 0) {
                $validator->errors()->add('salvage_value', 'The salvage value must be less than the purchase cost.');
            }
        });
    }
}
