<?php

namespace Tests\Feature\Inventory;

use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Issuance;
use Tests\TestCase;

class IssuanceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeItem(array $attrs = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'item_code'       => 'ITEM-' . uniqid(),
            'item_name'       => 'Test Laptop',
            'category'        => 'ICT Equipment',
            'unit_of_measure' => 'unit',
            'unit_cost'       => 50000.00,
            'quantity'        => 1,
            'condition'       => 'Serviceable',
            'status'          => 'In Stock',
            'fund_source'     => 'MOOE',
            'date_acquired'   => '2025-01-01',
        ], $attrs));
    }

    private function makeIssuance(Employee $employee, InventoryItem $item, array $attrs = []): Issuance
    {
        return Issuance::create(array_merge([
            'document_type'         => 'ICS',
            'inventory_item_id'     => $item->id,
            'issued_to_employee_id' => $employee->id,
            'issued_by'             => $employee->id,
            'issuance_number'       => 'ISS-' . uniqid(),
            'issued_date'           => '2025-06-01',
            'purpose'               => 'Official Use',
            'status'                => 'Active',
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_create_issuance(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $issuer   = Employee::factory()->create();
        $item     = $this->makeItem();

        $this->actingAs($admin)
            ->postJson('/api/issuances', [
                'document_type'         => 'ICS',
                'inventory_item_id'     => $item->uuid,
                'issued_to_employee_id' => $employee->uuid,
                'issued_by'             => $issuer->uuid,
                'issued_date'           => '2025-06-01',
                'purpose'               => 'Official Use',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.document_type', 'ICS');
    }

    public function test_create_issuance_requires_mandatory_fields(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->postJson('/api/issuances', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => [
                'document_type',
                'inventory_item_id',
                'issued_to_employee_id',
                'issued_by',
                'issued_date',
                'purpose',
            ]]);
    }

    public function test_create_issuance_with_unknown_item_uuid_returns_422(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/issuances', [
                'document_type'         => 'ICS',
                'inventory_item_id'     => '00000000-0000-0000-0000-000000000000',
                'issued_to_employee_id' => $employee->uuid,
                'issued_by'             => $employee->uuid,
                'issued_date'           => '2025-06-01',
                'purpose'               => 'Official Use',
            ])
            ->assertStatus(422);
    }

    public function test_teacher_cannot_create_issuance(): void
    {
        $teacher  = $this->userWithRole('Teacher/Staff');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        $this->actingAs($teacher)
            ->postJson('/api/issuances', [
                'document_type'         => 'ICS',
                'inventory_item_id'     => $item->uuid,
                'issued_to_employee_id' => $employee->uuid,
                'issued_by'             => $employee->uuid,
                'issued_date'           => '2025-06-01',
                'purpose'               => 'Official Use',
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/issuances', [])->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_view_issuance(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();
        $issuance = $this->makeIssuance($employee, $item);

        $this->actingAs($admin)
            ->getJson("/api/issuances/{$issuance->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $issuance->uuid);
    }

    public function test_unknown_issuance_uuid_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/issuances/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Acknowledge
    // -------------------------------------------------------------------------

    public function test_acknowledge_records_timestamp(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();
        $issuance = $this->makeIssuance($employee, $item);

        $this->assertNull($issuance->acknowledged_at);

        $this->actingAs($admin)
            ->putJson("/api/issuances/{$issuance->uuid}/acknowledge", [])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Acknowledgement recorded successfully.']);

        $this->assertNotNull($issuance->fresh()->acknowledged_at);
    }

    public function test_acknowledge_nonexistent_issuance_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->putJson('/api/issuances/00000000-0000-0000-0000-000000000000/acknowledge', [])
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Return
    // -------------------------------------------------------------------------

    public function test_record_return_sets_returned_status(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();
        $issuance = $this->makeIssuance($employee, $item, [
            'expected_return_date' => '2025-06-30',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/issuances/{$issuance->uuid}/return", [
                'actual_return_date'  => '2025-06-25',
                'condition_on_return' => 'Good',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Return recorded successfully.']);

        $this->assertDatabaseHas('issuances', [
            'id'     => $issuance->id,
            'status' => 'Returned',
        ]);
    }

    public function test_return_requires_date_and_condition(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();
        $issuance = $this->makeIssuance($employee, $item);

        $this->actingAs($admin)
            ->putJson("/api/issuances/{$issuance->uuid}/return", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['actual_return_date', 'condition_on_return']]);
    }

    // -------------------------------------------------------------------------
    // Transfer
    // -------------------------------------------------------------------------

    public function test_transfer_creates_new_issuance_and_marks_old_as_transferred(): void
    {
        $admin       = $this->userWithRole('Admin Officer');
        $employee    = Employee::factory()->create();
        $newEmployee = Employee::factory()->create();
        $item        = $this->makeItem();
        $issuance    = $this->makeIssuance($employee, $item);

        $this->actingAs($admin)
            ->putJson("/api/issuances/{$issuance->uuid}/transfer", [
                'new_employee_id' => $newEmployee->uuid,
                'remarks'         => 'Transferred to new custodian.',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Item transferred successfully.']);

        $this->assertDatabaseHas('issuances', [
            'id'     => $issuance->id,
            'status' => 'Transferred',
        ]);

        // A new Active issuance should exist for the new employee
        $this->assertDatabaseHas('issuances', [
            'issued_to_employee_id' => $newEmployee->id,
            'inventory_item_id'     => $item->id,
            'status'                => 'Active',
        ]);
    }

    public function test_transfer_requires_new_employee_id(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();
        $issuance = $this->makeIssuance($employee, $item);

        $this->actingAs($admin)
            ->putJson("/api/issuances/{$issuance->uuid}/transfer", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_employee_id']]);
    }

    // -------------------------------------------------------------------------
    // Overdue Detection
    // -------------------------------------------------------------------------

    public function test_overdue_endpoint_returns_past_due_active_issuances(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        // Overdue: expected_return_date in the past, still Active
        $this->makeIssuance($employee, $item, [
            'issuance_number'     => 'ISS-OVERDUE',
            'expected_return_date' => '2024-01-01',
        ]);

        // Not overdue: no expected_return_date
        $this->makeIssuance($employee, $item, [
            'issuance_number'     => 'ISS-CURRENT',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/issuances/overdue')
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));

        // Every returned record must have an expected_return_date in the past
        foreach ($data as $record) {
            $this->assertNotNull($record['expected_return_date']);
            $this->assertLessThan(now()->toDateString(), $record['expected_return_date']);
        }
    }
}
