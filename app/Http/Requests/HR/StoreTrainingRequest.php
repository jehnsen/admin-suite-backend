<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'        => ['required', 'integer', 'exists:employees,id'],
            'training_title'     => ['required', 'string', 'max:255'],
            'training_type'      => ['required', 'string', 'max:100'],
            'date_from'          => ['required', 'date'],
            'date_to'            => ['required', 'date', 'after_or_equal:date_from'],
            'description'        => ['nullable', 'string'],
            'conducted_by'       => ['nullable', 'string', 'max:255'],
            'venue'              => ['nullable', 'string', 'max:255'],
            'venue_type'         => ['nullable', 'string', 'max:50'],
            'number_of_hours'    => ['nullable', 'numeric', 'min:0'],
            'ld_units'           => ['nullable', 'numeric', 'min:0'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'certificate_date'   => ['nullable', 'date'],
            'sponsorship'        => ['nullable', 'string', 'max:255'],
            'cost'               => ['nullable', 'numeric', 'min:0'],
            'status'             => ['nullable', 'string', 'max:50'],
            'remarks'            => ['nullable', 'string'],
        ];
    }
}
