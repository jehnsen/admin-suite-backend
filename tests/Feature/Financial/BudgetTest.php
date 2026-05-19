<?php

namespace Tests\Feature\Financial;

use App\Models\Budget;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_create_budget(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/budgets', [
                'fund_source'      => 'MOOE',
                'fiscal_year'      => now()->year,
                'allocated_amount' => 250000.00,
                'budget_name'      => 'Q1 Office Supplies Budget',
                'category'         => 'Operating Expenses',
                'quarter'          => 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending')
            ->assertJsonPath('data.fund_source', 'MOOE')
            ->assertJsonPath('data.fiscal_year', now()->year);
    }

    public function test_budget_creation_requires_fund_source_and_fiscal_year(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/budgets', [
                'allocated_amount' => 100000.00,
                // missing fund_source and fiscal_year
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['fund_source', 'fiscal_year']]);
    }

    public function test_allocated_amount_must_be_non_negative(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/budgets', [
                'fund_source'      => 'MOOE',
                'fiscal_year'      => now()->year,
                'allocated_amount' => -500,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['allocated_amount']]);
    }

    public function test_teacher_cannot_create_budget(): void
    {
        $teacher = $this->userWithRole('Teacher/Staff');

        $this->actingAs($teacher)
            ->postJson('/api/budgets', [
                'fund_source'      => 'MOOE',
                'fiscal_year'      => now()->year,
                'allocated_amount' => 100000,
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_budgets(): void
    {
        $this->getJson('/api/budgets')->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Show & List
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_list_budgets(): void
    {
        Budget::factory()->count(5)->create();

        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->getJson('/api/budgets')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_unknown_budget_returns_404(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->getJson('/api/budgets/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function test_school_head_can_approve_pending_budget(): void
    {
        $budget = Budget::factory()->pending()->create();
        $head   = $this->userWithRole('School Head');

        $this->actingAs($head)
            ->putJson("/api/budgets/{$budget->uuid}/approve")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('budgets', [
            'id'     => $budget->id,
            'status' => 'Approved',
        ]);
    }

    public function test_admin_officer_cannot_approve_budget(): void
    {
        $budget = Budget::factory()->pending()->create();
        $admin  = $this->userWithRole('Admin Officer');

        // Admin Officer does not have approve_budget permission
        $this->actingAs($admin)
            ->putJson("/api/budgets/{$budget->uuid}/approve")
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Activate
    // -------------------------------------------------------------------------

    public function test_school_head_can_activate_approved_budget(): void
    {
        $budget = Budget::factory()->approved()->create();
        $head   = $this->userWithRole('School Head');

        $this->actingAs($head)
            ->putJson("/api/budgets/{$budget->uuid}/activate")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Active');
    }

    // -------------------------------------------------------------------------
    // Update & Delete
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_update_pending_budget(): void
    {
        $budget = Budget::factory()->pending()->create();
        $admin  = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->putJson("/api/budgets/{$budget->uuid}", [
                'budget_name' => 'Updated Budget Name',
                'fund_source' => $budget->fund_source,
                'fiscal_year' => $budget->fiscal_year,
                'allocated_amount' => $budget->allocated_amount,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.budget_name', 'Updated Budget Name');
    }

    public function test_admin_officer_can_delete_budget(): void
    {
        $budget = Budget::factory()->pending()->create();
        $admin  = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->deleteJson("/api/budgets/{$budget->uuid}")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Budget deleted successfully.']);

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    // -------------------------------------------------------------------------
    // Filter
    // -------------------------------------------------------------------------

    public function test_budgets_can_be_filtered_by_fiscal_year(): void
    {
        Budget::factory()->create(['fiscal_year' => 2025]);
        Budget::factory()->create(['fiscal_year' => 2026]);

        $user = $this->userWithRole('Admin Officer');

        $response = $this->actingAs($user)
            ->getJson('/api/budgets/fiscal-year/2025')
            ->assertStatus(200);

        $years = collect($response->json('data'))->pluck('fiscal_year')->unique()->values()->all();
        $this->assertEquals([2025], $years);
    }
}
