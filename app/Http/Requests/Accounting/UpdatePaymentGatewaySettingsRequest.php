<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentGatewaySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sslcommerz_enabled' => ['boolean'],
            'sslcommerz_store_id' => ['required_if:sslcommerz_enabled,true', 'nullable', 'string', 'max:255'],
            // Left blank on an edit, the existing stored password is kept —
            // see AccountingSettingsController::updatePaymentGateway().
            'sslcommerz_store_password' => ['nullable', 'string', 'max:255'],
            'sslcommerz_sandbox' => ['boolean'],
            'sslcommerz_currency' => ['nullable', 'string', 'max:10'],
        ];
    }
}
