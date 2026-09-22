<?php

namespace App\Http\Requests\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lines = collect($this->input('lines', []));

            if ($lines->isEmpty()) {
                return;
            }

            $accountIds = $lines->pluck('account_id')->filter()->unique()->all();
            $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

            $totalDebit = '0.0000';
            $totalCredit = '0.0000';

            foreach ($lines as $index => $line) {
                $debit = (string) ($line['debit'] ?? 0);
                $credit = (string) ($line['credit'] ?? 0);

                $hasDebit = bccomp($debit, '0', 4) > 0;
                $hasCredit = bccomp($credit, '0', 4) > 0;

                if ($hasDebit === $hasCredit) {
                    $validator->errors()->add(
                        "lines.{$index}",
                        'Each line must have either a debit or a credit amount, not both or neither.'
                    );
                }

                $account = $accounts->get($line['account_id'] ?? null);

                if ($account && ! $account->is_active) {
                    $validator->errors()->add(
                        "lines.{$index}.account_id",
                        'Cannot post to an inactive account.'
                    );
                }

                $totalDebit = bcadd($totalDebit, $debit, 4);
                $totalCredit = bcadd($totalCredit, $credit, 4);
            }

            if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
                $validator->errors()->add(
                    'lines',
                    "Total debit ({$totalDebit}) must equal total credit ({$totalCredit})."
                );
            } elseif (bccomp($totalDebit, '0', 4) === 0) {
                $validator->errors()->add('lines', 'The journal cannot be entirely zero.');
            }
        });
    }
}
