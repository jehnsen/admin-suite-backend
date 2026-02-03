<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller or middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // 10MB max
            ],
            'documentable_type' => [
                'required',
                'string',
                Rule::in(['Liquidation', 'PurchaseRequest', 'InventoryAdjustment']),
            ],
            'documentable_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'document_type' => [
                'required',
                'string',
                Rule::in([
                    'official_receipt',
                    'purchase_order',
                    'delivery_receipt',
                    'property_card_photo',
                    'iar',
                    'other',
                ]),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'Only PDF, JPG, JPEG, and PNG files are allowed.',
            'file.max' => 'File size must not exceed 10MB.',
            'documentable_type.required' => 'Entity type is required.',
            'documentable_type.in' => 'Invalid entity type selected.',
            'documentable_id.required' => 'Entity ID is required.',
            'documentable_id.integer' => 'Entity ID must be a valid number.',
            'document_type.required' => 'Document type is required.',
            'document_type.in' => 'Invalid document type selected.',
            'description.max' => 'Description must not exceed 500 characters.',
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that the entity exists
            $this->validateEntityExists($validator);

            // Validate document type is allowed for entity
            $this->validateDocumentTypeForEntity($validator);
        });
    }

    /**
     * Validate that the documentable entity exists
     */
    private function validateEntityExists($validator): void
    {
        $type = $this->input('documentable_type');
        $id = $this->input('documentable_id');

        if ($type && $id) {
            $modelClass = "App\\Models\\{$type}";

            if (!class_exists($modelClass)) {
                $validator->errors()->add('documentable_type', 'Invalid entity type.');
                return;
            }

            if (!$modelClass::find($id)) {
                $validator->errors()->add('documentable_id', "The selected {$type} does not exist.");
            }
        }
    }

    /**
     * Validate document type is allowed for entity type
     */
    private function validateDocumentTypeForEntity($validator): void
    {
        $entityType = $this->input('documentable_type');
        $documentType = $this->input('document_type');

        if (!$entityType || !$documentType) {
            return;
        }

        $allowedTypes = match ($entityType) {
            'Liquidation' => ['official_receipt', 'other'],
            'PurchaseRequest' => ['purchase_order', 'delivery_receipt', 'iar'],
            'InventoryAdjustment' => ['property_card_photo', 'iar', 'other'],
            default => [],
        };

        if (!in_array($documentType, $allowedTypes)) {
            $validator->errors()->add(
                'document_type',
                "Document type '{$documentType}' is not allowed for {$entityType}."
            );
        }
    }
}
