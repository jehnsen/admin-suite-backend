<?php

namespace Database\Factories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $allocated = fake()->randomFloat(2, 10000, 500000);

        return [
            'budget_code'      => 'BUDGET-' . fake()->unique()->numerify('######'),
            'budget_name'      => fake()->words(3, true) . ' Budget',
            'fund_source'      => fake()->randomElement(['MOOE', 'SEF', 'DepEd Central', 'LGU', 'Donation']),
            'fiscal_year'      => now()->year,
            'classification'   => fake()->randomElement(['SIP', 'AIP', 'GAA', 'Other']),
            'category'         => fake()->randomElement(['Personnel Services', 'Capital Outlay', 'Operating Expenses']),
            'description'      => fake()->sentence(),
            'allocated_amount' => $allocated,
            'utilized_amount'  => 0.00,
            'remaining_balance' => $allocated,
            'start_date'       => now()->startOfYear()->format('Y-m-d'),
            'end_date'         => now()->endOfYear()->format('Y-m-d'),
            'quarter'          => fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']),
            'status'           => 'Pending',
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

    public function active(): static
    {
        return $this->state(['status' => 'Active']);
    }
}
