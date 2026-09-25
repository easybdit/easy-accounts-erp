<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'off_days' => ['nullable', 'array'],
            'off_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
        ];
    }
}
