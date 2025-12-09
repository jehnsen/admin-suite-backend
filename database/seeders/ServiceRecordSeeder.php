<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceRecord;
use App\Models\Employee;

class ServiceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            // Create initial service record
            ServiceRecord::create([
                'employee_id' => $employee->id,
                'date_from' => $employee->date_hired,
                'date_to' => null, // Current position
                'designation' => $employee->position,
                'status_of_appointment' => $employee->employment_status,
                'salary_grade' => $employee->salary_grade,
                'step_increment' => $employee->step_increment,
                'monthly_salary' => $employee->monthly_salary,
                'station_place_of_assignment' => $employee->city,
                'office_entity' => 'Department of Education',
                'government_service' => 'Yes',
                'action_type' => 'New Appointment',
                'appointment_authority' => 'DepEd Order No. 123-2024',
                'appointment_date' => $employee->date_hired,
            ]);

            // Some employees have promotion history
            if (in_array($employee->position, ['Principal IV', 'Master Teacher I', 'Head Teacher VI'])) {
                // Add previous position
                $previousPosition = $this->getPreviousPosition($employee->position);

                ServiceRecord::create([
                    'employee_id' => $employee->id,
                    'date_from' => $employee->date_hired,
                    'date_to' => $employee->date_hired->copy()->addYears(5),
                    'designation' => $previousPosition['position'],
                    'status_of_appointment' => 'Permanent',
                    'salary_grade' => $previousPosition['sg'],
                    'step_increment' => 1,
                    'monthly_salary' => $previousPosition['salary'],
                    'station_place_of_assignment' => $employee->city,
                    'office_entity' => 'Department of Education',
                    'government_service' => 'Yes',
                    'action_type' => 'Promotion',
                    'appointment_authority' => 'DepEd Order No. 089-2019',
                    'appointment_date' => $employee->date_hired->copy()->addYears(5),
                ]);
            }
        }

        $this->command->info('Service records created successfully!');
    }

    private function getPreviousPosition(string $currentPosition): array
    {
        $positions = [
            'Principal IV' => ['position' => 'Principal III', 'sg' => 23, 'salary' => 85000],
            'Master Teacher I' => ['position' => 'Teacher III', 'sg' => 13, 'salary' => 32000],
            'Head Teacher VI' => ['position' => 'Head Teacher III', 'sg' => 17, 'salary' => 48000],
        ];

        return $positions[$currentPosition] ?? ['position' => 'Teacher I', 'sg' => 11, 'salary' => 25000];
    }
}
