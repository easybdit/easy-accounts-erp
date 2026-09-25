<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'special_working_day_grade_rates.A' => ['required', 'numeric', 'min:0'],
            'special_working_day_grade_rates.B' => ['required', 'numeric', 'min:0'],
            'special_working_day_grade_rates.C' => ['required', 'numeric', 'min:0'],
            'late_deduction_ratio' => ['nullable', 'integer', 'min:1', 'max:31'],
            'late_warning_threshold' => ['required', 'integer', 'min:1', 'max:31'],
            'multi_step_leave_approval_enabled' => ['boolean'],
        ];
    }
}
