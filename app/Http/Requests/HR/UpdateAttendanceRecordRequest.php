<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_attendance');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'attendance_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'time_in' => ['nullable', 'date_format:H:i:s'],
            'time_out' => ['nullable', 'date_format:H:i:s', 'after:time_in'],
            'lunch_out' => ['nullable', 'date_format:H:i:s'],
            'lunch_in' => ['nullable', 'date_format:H:i:s', 'after:lunch_out'],
            'status' => ['sometimes', Rule::in([
                'Present',
                'Absent',
                'On Leave',
                'Half-Day',
                'Holiday',
                'Weekend',
                'Service Credit Used'
            ])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'attendance_date.before_or_equal' => 'Cannot set future attendance date.',
            'time_in.date_format' => 'Time in must be in HH:MM:SS format.',
            'time_out.date_format' => 'Time out must be in HH:MM:SS format.',
            'time_out.after' => 'Time out must be after time in.',
            'lunch_out.date_format' => 'Lunch out must be in HH:MM:SS format.',
            'lunch_in.date_format' => 'Lunch in must be in HH:MM:SS format.',
            'lunch_in.after' => 'Lunch in must be after lunch out.',
            'status.in' => 'Invalid attendance status.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'attendance_date' => 'attendance date',
            'time_in' => 'time in',
            'time_out' => 'time out',
            'lunch_out' => 'lunch out',
            'lunch_in' => 'lunch in',
        ];
    }
}
