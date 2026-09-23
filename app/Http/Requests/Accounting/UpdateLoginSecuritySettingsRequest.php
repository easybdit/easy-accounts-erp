<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoginSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_max_attempts' => ['required', 'integer', 'min:3', 'max:20'],
            'login_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'login_captcha_enabled' => ['boolean'],
        ];
    }
}
