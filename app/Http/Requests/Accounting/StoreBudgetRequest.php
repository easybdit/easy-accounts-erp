<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id', 'distinct'],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $accountIds = collect($this->input('lines', []))->pluck('account_id')->filter()->unique();
            $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

            foreach ($this->input('lines', []) as $index => $line) {
                $account = $accounts->get($line['account_id'] ?? null);

                if ($account && ! in_array($account->type, ['income', 'expense'], true)) {
                    $validator->errors()->add("lines.{$index}.account_id", 'A budget line must use an income or expense account.');
                }
            }
        });
    }
}
