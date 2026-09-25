<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendancePunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists((new Employee)->getTable(), 'id')],
            'type' => ['required', Rule::in(['check_in', 'check_out'])],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
        ];
    }
}
