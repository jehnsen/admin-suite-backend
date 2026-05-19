<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        return [
            'pr_number'        => 'PR-' . now()->format('Y') . '-' . fake()->unique()->numerify('####'),
            'pr_date'          => now()->format('Y-m-d'),
            'requested_by'     => \App\Models\User::factory(),
            'purpose'          => fake()->sentence(),
            'fund_source'      => fake()->randomElement(['MOOE', 'SEF', 'Other']),
            'department'       => 'Administration',
            'section'          => null,
            'procurement_mode' => 'Shopping',
            'estimated_budget' => fake()->randomFloat(2, 1000, 100000),
            'status'           => 'Draft',
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'Draft']);
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'Submitted']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'Approved']);
    }
}
