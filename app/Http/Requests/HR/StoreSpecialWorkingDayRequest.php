<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialWorkingDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists((new Employee)->getTable(), 'id')],
            'date' => ['required', 'date'],
            'is_payable' => ['boolean'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
