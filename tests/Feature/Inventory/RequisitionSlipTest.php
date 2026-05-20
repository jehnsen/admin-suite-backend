<?php

namespace Tests\Feature\Inventory;

use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\RequisitionSlip;
use App\Models\RequisitionSlipItem;
use Tests\TestCase;

class RequisitionSlipTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeItem(array $attrs = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'item_code'       => 'ITEM-' . uniqid(),
            'item_name'       => 'Bond Paper (500 sheets/ream)',
            'category'        => 'Office Supplies',
            'unit_of_measure' => 'ream',
            'unit_cost'       => 250.00,
            'quantity'        => 50,
            'condition'       => 'Serviceable',
            'status'          => 'In Stock',
            'fund_source'     => 'MOOE',
            'date_acquired'   => '2025-01-01',
        ], $attrs));
    }

    private function makeRis(Employee $requester, array $attrs = []): RequisitionSlip
    {
        return RequisitionSlip::create(array_merge([
            'ris_number'               => 'RIS-' . uniqid(),
            'requested_by_employee_id' => $requester->id,
            'division_office'          => 'Office of the Principal',
            'purpose'                  => 'For daily office operations',
            'status'                   => 'Pending',
            'requested_date'           => '2025-06-01',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_create_ris_with_items(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        $this->actingAs($admin)
            ->postJson('/api/requisition-slips', [
                'requested_by_employee_id' => $employee->id,
                'purpose'                  => 'Office supplies for Q2 operations',
                'requested_date'           => '2025-06-01',
                'division_office'          => 'Administrative Division',
                'status'                   => 'Pending',
                'items' => [
                    [
                        'inventory_item_id'  => $item->id,
                        'quantity_requested' => 10,
                        'unit_of_measure'    => 'ream',
                    ],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending');
    }

    public function test_create_ris_requires_at_least_one_item(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/requisition-slips', [
                'requested_by_employee_id' => $employee->id,
                'purpose'                  => 'Test',
                'requested_date'           => '2025-06-01',
                'items'                    => [],
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_create_ris_requires_purpose_and_date(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        $this->actingAs($admin)
            ->postJson('/api/requisition-slips', [
                'requested_by_employee_id' => $employee->id,
                'items' => [
                    ['inventory_item_id' => $item->id, 'quantity_requested' => 1, 'unit_of_measure' => 'ream'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['purpose', 'requested_date']]);
    }

    public function test_teacher_cannot_create_ris(): void
    {
        $teacher  = $this->userWithRole('Teacher/Staff');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        $this->actingAs($teacher)
            ->postJson('/api/requisition-slips', [
                'requested_by_employee_id' => $employee->id,
                'purpose'                  => 'Test',
                'requested_date'           => '2025-06-01',
                'items' => [
                    ['inventory_item_id' => $item->id, 'quantity_requested' => 1, 'unit_of_measure' => 'ream'],
                ],
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Show & List
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_view_ris(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $slip     = $this->makeRis($employee);

        $this->actingAs($admin)
            ->getJson("/api/requisition-slips/{$slip->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $slip->uuid);
    }

    public function test_unknown_ris_uuid_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/requisition-slips/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Workflow: Pending → Approved → Released
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_approve_pending_ris(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $approver = Employee::factory()->create();
        $item     = $this->makeItem();
        $slip     = $this->makeRis($employee, ['status' => 'Pending']);

        RequisitionSlipItem::create([
            'requisition_slip_id' => $slip->id,
            'inventory_item_id'   => $item->id,
            'quantity_requested'  => 10,
            'unit_of_measure'     => 'ream',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/requisition-slips/{$slip->uuid}/approve", [
                'approved_by_employee_id' => $approver->id,
                'approved_quantities'     => [$item->id => 8],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('requisition_slips', [
            'id'     => $slip->id,
            'status' => 'Approved',
        ]);
    }

    public function test_approve_ris_requires_approver_and_quantities(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $slip     = $this->makeRis($employee);

        $this->actingAs($admin)
            ->putJson("/api/requisition-slips/{$slip->uuid}/approve", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['approved_by_employee_id', 'approved_quantities']]);
    }

    public function test_admin_officer_can_release_approved_ris(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $releaser = Employee::factory()->create();
        $item     = $this->makeItem();
        $slip     = $this->makeRis($employee, ['status' => 'Approved']);

        RequisitionSlipItem::create([
            'requisition_slip_id' => $slip->id,
            'inventory_item_id'   => $item->id,
            'quantity_requested'  => 10,
            'quantity_approved'   => 8,
            'unit_of_measure'     => 'ream',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/requisition-slips/{$slip->uuid}/release", [
                'released_by_employee_id' => $releaser->id,
                'issued_quantities'       => [$item->id => 8],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Released');

        $this->assertDatabaseHas('requisition_slips', [
            'id'     => $slip->id,
            'status' => 'Released',
        ]);
    }

    public function test_school_head_can_approve_pending_ris(): void
    {
        $head     = $this->userWithRole('School Head');
        $employee = Employee::factory()->create();
        $approver = Employee::factory()->create();
        $item     = $this->makeItem();
        $slip     = $this->makeRis($employee, ['status' => 'Pending']);

        RequisitionSlipItem::create([
            'requisition_slip_id' => $slip->id,
            'inventory_item_id'   => $item->id,
            'quantity_requested'  => 5,
            'unit_of_measure'     => 'box',
        ]);

        $this->actingAs($head)
            ->putJson("/api/requisition-slips/{$slip->uuid}/approve", [
                'approved_by_employee_id' => $approver->id,
                'approved_quantities'     => [$item->id => 5],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_cancel_ris(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $slip     = $this->makeRis($employee);

        $this->actingAs($admin)
            ->putJson("/api/requisition-slips/{$slip->uuid}/cancel", [
                'remarks' => 'No longer needed.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Cancelled');
    }
}
