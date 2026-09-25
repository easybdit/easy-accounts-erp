<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique((new Department)->getTable(), 'name')->ignore($department)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
