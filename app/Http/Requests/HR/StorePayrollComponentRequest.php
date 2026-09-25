<?php

namespace App\Http\Requests\HR;

use App\Models\Accounting\Account;
use App\Models\HR\PayrollComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePayrollComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $component = $this->route('payroll_component');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(PayrollComponent::TYPES)],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'source_component_key' => [
                'required', 'string', 'max:255',
                Rule::unique('payroll_components', 'source_component_key')->ignore($component),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = Account::find($this->input('account_id'));

            if ($account === null) {
                return;
            }

            $allowedTypes = $this->input('type') === 'earning'
                ? ['expense']
                : ['liability', 'expense'];

            if (! in_array($account->type, $allowedTypes, true)) {
                $validator->errors()->add(
                    'account_id',
                    $this->input('type') === 'earning'
                        ? 'An earning component must post to an expense account.'
                        : 'A deduction component must post to a liability or expense account.'
                );
            }
        });
    }
}
