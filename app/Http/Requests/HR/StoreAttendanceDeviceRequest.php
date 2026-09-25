<?php

namespace App\Http\Requests\HR;

use Easybdit\LaravelEasyAttendance\Models\AttendanceDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $device = $this->route('attendance_device');

        return [
            'name' => ['required', 'string', 'max:100'],
            'ip' => ['nullable', 'ip', 'required_without:serial_number'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'comm_key' => ['nullable', 'string', 'max:20'],
            'serial_number' => [
                'nullable', 'string', 'max:50', 'required_without:ip',
                Rule::unique((new AttendanceDevice)->getTable(), 'serial_number')->ignore($device),
            ],
            'model' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
