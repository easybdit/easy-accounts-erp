<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIpWhitelistEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'string', 'max:50', Rule::unique('ip_whitelist_entries', 'ip_address')],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $value = $this->string('ip_address')->trim()->toString();

            if ($value === '') {
                return;
            }

            [$ip, $bits] = str_contains($value, '/') ? explode('/', $value, 2) : [$value, null];

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $validator->errors()->add('ip_address', 'Enter a valid IPv4 address, optionally with a /CIDR suffix (e.g. 203.0.113.0/24).');

                return;
            }

            if ($bits !== null && (! ctype_digit($bits) || (int) $bits > 32)) {
                $validator->errors()->add('ip_address', 'The CIDR suffix must be a number from 0 to 32.');
            }
        });
    }
}
