<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->uuid,
            'log_date'   => $this->log_date?->format('Y-m-d'),
            'punched_at' => $this->punched_at?->format('Y-m-d H:i:s'),
            'source'     => $this->source,
            'employee'   => $this->whenLoaded('employee', fn() => [
                'id'              => $this->employee->uuid,
                'employee_number' => $this->employee->employee_number,
                'full_name'       => $this->employee->full_name,
            ]),
        ];
    }
}
