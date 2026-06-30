<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreScansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scans' => ['required', 'array', 'min:1', 'max:500'],
            'scans.*.client_uuid' => ['required', 'uuid'],
            'scans.*.qr_token' => ['required', 'string'],
            'scans.*.timing_point_id' => ['required', 'integer'],
            'scans.*.scanned_at' => ['required', 'date'],
            'scans.*.raw_device_time' => ['nullable', 'date'],
            'scans.*.clock_offset_ms' => ['nullable', 'integer'],
            'scans.*.source' => ['nullable', 'in:scan,manual,video'],
            'scans.*.device_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
