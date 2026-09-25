<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveType = $this->route('leave_type');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique((new LeaveType)->getTable(), 'name')->ignore($leaveType)],
            'days_allowed_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
