<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceImportBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->uuid,
            'original_file_name' => $this->original_file_name,
            'period_start'       => $this->period_start?->format('Y-m-d'),
            'period_end'         => $this->period_end?->format('Y-m-d'),
            'record_count'       => $this->record_count,
            'processed_count'    => $this->processed_count,
            'error_count'        => $this->error_count,
            'status'             => $this->status,
            'error_message'      => $this->error_message,
            'uploaded_by'        => $this->whenLoaded('uploader', fn() => [
                'id'   => $this->uploader->uuid,
                'name' => $this->uploader->name,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
