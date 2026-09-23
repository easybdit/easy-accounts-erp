<?php

namespace App\Http\Requests\Purchases;

use App\Models\Accounting\Account;
use App\Models\Purchases\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'withholding_tax_rate_id' => ['nullable', 'integer', 'exists:withholding_tax_rates,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.bill_id' => ['required', 'integer', 'exists:bills,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $paymentAccountId = $this->input('payment_account_id');

            if ($paymentAccountId) {
                $account = Account::find($paymentAccountId);

                if ($account && $account->type !== 'asset') {
                    $validator->errors()->add('payment_account_id', 'The payment account must be an asset account.');
                }

                if ($account && ! $account->is_active) {
                    $validator->errors()->add('payment_account_id', 'The payment account is inactive.');
                }
            }

            $allocations = collect($this->input('allocations', []));
            $vendorId = $this->input('vendor_id');
            $amount = (string) $this->input('amount', '0');

            $billIds = $allocations->pluck('bill_id')->filter();

            if ($billIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('allocations', 'The same bill cannot be allocated more than once in a single payment.');
            }

            $bills = Bill::whereIn('id', $billIds->unique())->get()->keyBy('id');
            $allocatedTotal = '0.0000';

            foreach ($allocations as $index => $allocation) {
                $bill = $bills->get($allocation['bill_id'] ?? null);
                $allocationAmount = (string) ($allocation['amount'] ?? 0);
                $allocatedTotal = bcadd($allocatedTotal, $allocationAmount, 4);

                if (! $bill) {
                    continue;
                }

                if ((int) $bill->vendor_id !== (int) $vendorId) {
                    $validator->errors()->add("allocations.{$index}.bill_id", 'This bill does not belong to the selected vendor.');
                }

                if ($bill->status !== 'posted') {
                    $validator->errors()->add("allocations.{$index}.bill_id", 'Only posted bills can receive a payment.');
                }

                if (bccomp($allocationAmount, $bill->amountDue(), 4) > 0) {
                    $validator->errors()->add(
                        "allocations.{$index}.amount",
                        "Amount exceeds the bill's remaining due of {$bill->amountDue()}."
                    );
                }
            }

            if ($vendorId && bccomp($allocatedTotal, $amount, 4) !== 0) {
                $validator->errors()->add(
                    'allocations',
                    "Allocated total ({$allocatedTotal}) must equal the payment amount ({$amount})."
                );
            }
        });
    }
}
