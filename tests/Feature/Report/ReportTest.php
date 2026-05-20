<?php

namespace Tests\Feature\Report;

use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\RequisitionSlip;
use App\Models\RequisitionSlipItem;
use App\Models\ServiceRecord;
use App\Models\Training;
use Tests\TestCase;

class ReportTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeItem(array $attrs = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'item_code'       => 'ITEM-' . uniqid(),
            'item_name'       => 'Bond Paper',
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

    // -------------------------------------------------------------------------
    // Auth & Permission Gates
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/reports/form6/some-uuid')->assertStatus(401);
    }

    public function test_teacher_without_export_reports_permission_is_denied(): void
    {
        $teacher      = $this->userWithRole('Teacher/Staff');
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($teacher)
            ->getJson("/api/reports/form6/{$leaveRequest->uuid}")
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Form 6 (Leave Request)
    // -------------------------------------------------------------------------

    public function test_form6_returns_correct_data_shape(): void
    {
        $admin        = $this->userWithRole('Admin Officer');
        $leaveRequest = LeaveRequest::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/reports/form6/{$leaveRequest->uuid}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'leave_request' => [
                    'id',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'days_requested',
                    'status',
                ],
                'employee' => [
                    'id',
                    'employee_number',
                    'full_name',
                    'position',
                ],
                'recommender',
                'approver',
            ],
        ]);

        $this->assertSame($leaveRequest->uuid, $response->json('data.leave_request.id'));
    }

    public function test_form6_with_unknown_uuid_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/reports/form6/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Leave request not found.']);
    }

    // -------------------------------------------------------------------------
    // RIS Report
    // -------------------------------------------------------------------------

    public function test_ris_report_returns_correct_data_shape(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();
        $item     = $this->makeItem();

        $slip = RequisitionSlip::create([
            'ris_number'               => 'RIS-RPT-001',
            'requested_by_employee_id' => $employee->id,
            'purpose'                  => 'Replenishment of supplies',
            'status'                   => 'Pending',
            'requested_date'           => '2025-06-01',
        ]);

        RequisitionSlipItem::create([
            'requisition_slip_id' => $slip->id,
            'inventory_item_id'   => $item->id,
            'quantity_requested'  => 10,
            'unit_of_measure'     => 'ream',
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/reports/ris/{$slip->uuid}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'slip'         => ['id', 'ris_number', 'date', 'purpose', 'status'],
                'requested_by' => ['id', 'full_name', 'position'],
                'approved_by',
                'released_by',
                'items',
            ],
        ]);

        $this->assertSame($slip->uuid, $response->json('data.slip.id'));
        $this->assertSame('2025-06-01', $response->json('data.slip.date'));
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_ris_report_with_unknown_uuid_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/reports/ris/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Requisition slip not found.']);
    }

    // -------------------------------------------------------------------------
    // PDS Report (Personal Data Sheet)
    // -------------------------------------------------------------------------

    public function test_pds_report_returns_correct_data_shape(): void
    {
        $admin    = $this->userWithRole('Admin Officer');
        $employee = Employee::factory()->create();

        ServiceRecord::create([
            'employee_id'                  => $employee->id,
            'date_from'                    => '2020-01-01',
            'date_to'                      => '2023-12-31',
            'designation'                  => 'Teacher I',
            'status_of_appointment'        => 'Permanent',
            'office_entity'                => 'DepEd Region VIII',
            'station_place_of_assignment'  => 'DepEd Division Office',
            'salary_grade'                 => 11,
            'step_increment'               => 1,
            'monthly_salary'               => 25439.00,
            'government_service'           => 'Yes',
            'action_type'                  => 'New Appointment',
        ]);

        Training::create([
            'employee_id'     => $employee->id,
            'training_title'  => 'LAC Session on Numeracy',
            'training_type'   => 'Seminar',
            'date_from'       => '2024-03-01',
            'date_to'         => '2024-03-02',
            'number_of_hours' => 8,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/reports/pds/{$employee->uuid}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'employee' => [
                    'id',
                    'employee_number',
                    'full_name',
                    'first_name',
                    'last_name',
                    'position',
                    'employment_status',
                    'salary_grade',
                ],
                'service_records' => [
                    '*' => ['id', 'date_from', 'date_to', 'designation', 'status_of_appointment'],
                ],
                'trainings' => [
                    '*' => ['id', 'training_title', 'date_from', 'date_to', 'number_of_hours'],
                ],
            ],
        ]);

        $this->assertSame($employee->uuid, $response->json('data.employee.id'));
        $this->assertCount(1, $response->json('data.service_records'));
        $this->assertCount(1, $response->json('data.trainings'));
    }

    public function test_pds_report_with_unknown_uuid_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/reports/pds/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Employee not found.']);
    }

    // -------------------------------------------------------------------------
    // School Head can also export reports
    // -------------------------------------------------------------------------

    public function test_school_head_can_access_form6_report(): void
    {
        $head         = $this->userWithRole('School Head');
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($head)
            ->getJson("/api/reports/form6/{$leaveRequest->uuid}")
            ->assertStatus(200);
    }
}
