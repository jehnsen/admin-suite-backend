<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTimeRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->uuid,
            'log_date'              => $this->log_date?->format('Y-m-d'),
            'day_of_week'           => $this->log_date?->format('D'),
            'time_in'               => $this->time_in,
            'time_out'              => $this->time_out,
            'hours_worked'          => (float) ($this->hours_worked ?? 0),
            'late_minutes'          => (int) ($this->late_minutes ?? 0),
            'undertime_minutes'     => (int) ($this->undertime_minutes ?? 0),
            'is_absent'             => (bool) $this->is_absent,
            'is_half_day'           => (bool) $this->is_half_day,
            'is_holiday'            => (bool) $this->is_holiday,
            'is_rest_day'           => (bool) $this->is_rest_day,
            'is_manually_corrected' => (bool) $this->is_manually_corrected,
            'correction_reason'     => $this->correction_reason,
            'employee'              => $this->whenLoaded('employee', fn() => [
                'id'              => $this->employee->uuid,
                'employee_number' => $this->employee->employee_number,
                'full_name'       => $this->employee->full_name,
                'position'        => $this->employee->position,
            ]),
            'import_batch' => $this->whenLoaded('importBatch', fn() => [
                'id'                 => $this->importBatch->uuid,
                'original_file_name' => $this->importBatch->original_file_name,
                'period_start'       => $this->importBatch->period_start?->format('Y-m-d'),
                'period_end'         => $this->importBatch->period_end?->format('Y-m-d'),
            ]),
            'corrector' => $this->whenLoaded('corrector', fn() => $this->corrector ? [
                'id'   => $this->corrector->uuid,
                'name' => $this->corrector->name,
            ] : null),
            'corrected_at' => $this->corrected_at?->format('Y-m-d H:i:s'),
            'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
