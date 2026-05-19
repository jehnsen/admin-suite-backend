<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 30));
        $end   = $start->copy()->addDays(fake()->numberBetween(0, 5));

        return [
            'employee_id'    => Employee::factory(),
            'leave_type'     => 'Vacation Leave',
            'start_date'     => $start->format('Y-m-d'),
            'end_date'       => $end->format('Y-m-d'),
            'days_requested' => $start->diffInDaysFiltered(fn($d) => !$d->isWeekend(), $end) ?: 1,
            'reason'         => fake()->sentence(),
            'status'         => 'Pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'Pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'Approved']);
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(['employee_id' => $employee->id]);
    }
}
