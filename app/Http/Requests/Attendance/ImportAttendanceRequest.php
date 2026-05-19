<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ImportAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'         => 'required|file|mimes:csv,txt|max:10240',
            'period_start' => 'required|date|date_format:Y-m-d',
            'period_end'   => 'required|date|date_format:Y-m-d|after_or_equal:period_start',
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes'              => 'The attendance file must be a CSV file (.csv).',
            'file.max'                => 'The file must not exceed 10 MB.',
            'period_start.required'   => 'Please specify the start date of the attendance period.',
            'period_end.required'     => 'Please specify the end date of the attendance period.',
            'period_end.after_or_equal' => 'The period end date must be on or after the start date.',
        ];
    }
}
