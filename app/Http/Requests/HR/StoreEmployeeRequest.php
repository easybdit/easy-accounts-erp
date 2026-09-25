<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Designation;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_code' => ['required', 'string', 'max:255', Rule::unique((new Employee)->getTable(), 'employee_code')->ignore($employee)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', Rule::exists((new Department)->getTable(), 'id')],
            'designation_id' => ['nullable', 'integer', Rule::exists((new Designation)->getTable(), 'id')],
            'device_user_id' => ['nullable', 'string', 'max:255', Rule::unique((new Employee)->getTable(), 'device_user_id')->ignore($employee)],
            'grade' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'array'],
            'allowances.*' => ['numeric', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
