<?php

namespace App\Http\Requests\Sales;

use App\Models\Accounting\Account;
use App\Models\Sales\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'deposit_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $depositAccountId = $this->input('deposit_account_id');

            if ($depositAccountId) {
                $account = Account::find($depositAccountId);

                if ($account && $account->type !== 'asset') {
                    $validator->errors()->add('deposit_account_id', 'The deposit account must be an asset account.');
                }

                if ($account && ! $account->is_active) {
                    $validator->errors()->add('deposit_account_id', 'The deposit account is inactive.');
                }
            }

            $allocations = collect($this->input('allocations', []));
            $customerId = $this->input('customer_id');
            $amount = (string) $this->input('amount', '0');

            $invoiceIds = $allocations->pluck('invoice_id')->filter();

            if ($invoiceIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('allocations', 'The same invoice cannot be allocated more than once in a single payment.');
            }

            $invoices = Invoice::whereIn('id', $invoiceIds->unique())->get()->keyBy('id');
            $allocatedTotal = '0.0000';

            foreach ($allocations as $index => $allocation) {
                $invoice = $invoices->get($allocation['invoice_id'] ?? null);
                $allocationAmount = (string) ($allocation['amount'] ?? 0);
                $allocatedTotal = bcadd($allocatedTotal, $allocationAmount, 4);

                if (! $invoice) {
                    continue;
                }

                if ((int) $invoice->customer_id !== (int) $customerId) {
                    $validator->errors()->add("allocations.{$index}.invoice_id", 'This invoice does not belong to the selected customer.');
                }

                if ($invoice->status !== 'posted') {
                    $validator->errors()->add("allocations.{$index}.invoice_id", 'Only posted invoices can receive a payment.');
                }

                if (bccomp($allocationAmount, $invoice->amountDue(), 4) > 0) {
                    $validator->errors()->add(
                        "allocations.{$index}.amount",
                        "Amount exceeds the invoice's remaining due of {$invoice->amountDue()}."
                    );
                }
            }

            if ($customerId && bccomp($allocatedTotal, $amount, 4) !== 0) {
                $validator->errors()->add(
                    'allocations',
                    "Allocated total ({$allocatedTotal}) must equal the payment amount ({$amount})."
                );
            }
        });
    }
}
