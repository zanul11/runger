<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreMarshalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'username' => ['required', 'string', 'max:191', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', Password::min(6)],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'timing_point_id' => ['required', 'integer', 'exists:gtr_timing_points,id'],
        ];
    }
}
