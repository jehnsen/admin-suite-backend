<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CorrectDtrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_in'           => 'nullable|date_format:H:i:s',
            'time_out'          => 'nullable|date_format:H:i:s|after:time_in',
            'is_absent'         => 'nullable|boolean',
            'is_half_day'       => 'nullable|boolean',
            'correction_reason' => 'required|string|max:500',
        ];
    }
}
