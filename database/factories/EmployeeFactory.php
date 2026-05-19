<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id'             => null,
            'employee_number'     => fake()->unique()->numerify('EMP####'),
            'first_name'          => fake()->firstName(),
            'middle_name'         => fake()->optional(0.7)->lastName(),
            'last_name'           => fake()->lastName(),
            'suffix'              => null,
            'date_of_birth'       => fake()->dateTimeBetween('-55 years', '-22 years')->format('Y-m-d'),
            'gender'              => fake()->randomElement(['Male', 'Female']),
            'civil_status'        => fake()->randomElement(['Single', 'Married', 'Widowed']),
            'email'               => fake()->unique()->safeEmail(),
            'mobile_number'       => '09' . fake()->numerify('#########'),
            'address'             => fake()->streetAddress(),
            'city'                => fake()->city(),
            'province'            => fake()->state(),
            'zip_code'            => fake()->numerify('####'),
            'plantilla_item_no'   => fake()->unique()->numerify('PLT-####'),
            'position'            => 'Teacher I',
            'position_title'      => 'Teacher I',
            'salary_grade'        => 11,
            'step_increment'      => 1,
            'monthly_salary'      => 25439.00,
            'employment_status'   => 'Permanent',
            'date_hired'          => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'vacation_leave_credits' => 15.00,
            'sick_leave_credits'  => 15.00,
            'standard_time_in'    => '08:00:00',
            'standard_time_out'   => '17:00:00',
            'status'              => 'Active',
        ];
    }

    public function withCredits(float $vacation = 15.0, float $sick = 15.0): static
    {
        return $this->state([
            'vacation_leave_credits' => $vacation,
            'sick_leave_credits'     => $sick,
        ]);
    }
}
