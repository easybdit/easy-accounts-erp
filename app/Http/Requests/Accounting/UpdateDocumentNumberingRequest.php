<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentNumberingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rule = ['nullable', 'string', 'max:10', 'alpha_dash'];

        return [
            'invoice' => $rule,
            'estimate' => $rule,
            'credit_note' => $rule,
            'payment' => $rule,
            'bill' => $rule,
            'purchase_order' => $rule,
            'vendor_credit' => $rule,
            'vendor_payment' => $rule,
            'expense' => $rule,
            'bank_deposit' => $rule,
            'transfer' => $rule,
            'sales_receipt' => $rule,
        ];
    }
}
