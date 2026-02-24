<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'training_title'     => ['sometimes', 'string', 'max:255'],
            'training_type'      => ['sometimes', 'string', 'max:100'],
            'date_from'          => ['sometimes', 'date'],
            'date_to'            => ['sometimes', 'date'],
            'description'        => ['sometimes', 'nullable', 'string'],
            'conducted_by'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'venue'              => ['sometimes', 'nullable', 'string', 'max:255'],
            'venue_type'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'number_of_hours'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'ld_units'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'certificate_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'certificate_date'   => ['sometimes', 'nullable', 'date'],
            'sponsorship'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'cost'               => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status'             => ['sometimes', 'nullable', 'string', 'max:50'],
            'remarks'            => ['sometimes', 'nullable', 'string'],
        ];
    }
}
