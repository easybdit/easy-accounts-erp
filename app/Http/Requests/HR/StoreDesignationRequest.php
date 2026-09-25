<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', Rule::exists((new Department)->getTable(), 'id')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
