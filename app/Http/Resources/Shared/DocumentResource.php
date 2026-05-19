<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->uuid,
            'document_type' => $this->document_type,
            'file_name'     => $this->file_name,
            'file_size'     => $this->file_size,
            'file_size_human' => $this->file_size_human,
            'mime_type'     => $this->mime_type,
            'description'   => $this->description,
            'is_mandatory'  => (bool) $this->is_mandatory,
            'is_sensitive'  => (bool) $this->is_sensitive,
            'file_url'      => $this->file_url,   // computed: signed URL for private, direct URL for public
            'uploaded_at'   => $this->uploaded_at?->format('Y-m-d H:i:s'),
            // file_path, storage_disk, documentable_id, documentable_type intentionally omitted

            'uploaded_by' => $this->whenLoaded('uploadedBy', fn() => [
                'id'   => $this->uploadedBy->uuid,
                'name' => $this->uploadedBy->name,
            ]),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
