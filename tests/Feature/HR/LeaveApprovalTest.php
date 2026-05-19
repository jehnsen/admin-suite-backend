<?php

namespace Tests\Feature\HR;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Tests\TestCase;

class LeaveApprovalTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_create_leave_request(): void
    {
        [$user, $employee] = $this->userWithEmployee('Admin Officer');

        $start = now()->addDay()->format('Y-m-d');
        $end   = now()->addDays(3)->format('Y-m-d');

        $this->actingAs($user)
            ->postJson('/api/leave-requests', [
                'employee_id'    => $employee->id,
                'leave_type'     => 'Vacation Leave',
                'start_date'     => $start,
                'end_date'       => $end,
                'days_requested' => 3,
                'reason'         => 'Family event',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending')
            ->assertJsonPath('data.leave_type', 'Vacation Leave');
    }

    public function test_leave_request_requires_employee_and_leave_type(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/leave-requests', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['employee_id', 'leave_type', 'start_date', 'end_date']]);
    }

    public function test_invalid_leave_type_is_rejected(): void
    {
        [$user, $employee] = $this->userWithEmployee('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/leave-requests', [
                'employee_id' => $employee->id,
                'leave_type'  => 'Invalid Leave Type',
                'start_date'  => now()->addDay()->format('Y-m-d'),
                'end_date'    => now()->addDays(2)->format('Y-m-d'),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['leave_type']]);
    }

    public function test_start_date_must_not_be_in_the_past(): void
    {
        [$user, $employee] = $this->userWithEmployee('Admin Officer');

        $this->actingAs($user)
            ->postJson('/api/leave-requests', [
                'employee_id' => $employee->id,
                'leave_type'  => 'Vacation Leave',
                'start_date'  => now()->subDay()->format('Y-m-d'),
                'end_date'    => now()->format('Y-m-d'),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['start_date']]);
    }

    public function test_teacher_cannot_create_leave_request_for_another_employee(): void
    {
        // Teacher has create_leave_request permission but policy restricts...
        // The route allows teachers to create leave, but only for themselves
        $teacher  = $this->userWithRole('Teacher/Staff');
        $employee = Employee::factory()->create(); // unlinked employee

        $this->actingAs($teacher)
            ->postJson('/api/leave-requests', [
                'employee_id' => $employee->id,
                'leave_type'  => 'Vacation Leave',
                'start_date'  => now()->addDay()->format('Y-m-d'),
                'end_date'    => now()->addDays(2)->format('Y-m-d'),
            ])
            ->assertStatus(422); // Forbidden by policy (own record only)
    }

    // -------------------------------------------------------------------------
    // Recommend
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_recommend_leave_request(): void
    {
        // Create the leave request employee (different from admin officer)
        $employee = Employee::factory()->create();
        $leave    = LeaveRequest::factory()->forEmployee($employee)->create();

        // Admin officer recommends (has recommend_leave permission)
        [$adminUser, $adminEmployee] = $this->userWithEmployee('Admin Officer');

        $this->actingAs($adminUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/recommend", [
                'recommended_by' => $adminEmployee->id,
                'remarks'        => 'Approved for recommendation.',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Leave request recommended successfully.']);

        $this->assertDatabaseHas('leave_requests', [
            'id'             => $leave->id,
            'recommended_by' => $adminEmployee->id,
        ]);
    }

    public function test_admin_officer_cannot_recommend_their_own_leave(): void
    {
        [$adminUser, $adminEmployee] = $this->userWithEmployee('Admin Officer');
        $leave = LeaveRequest::factory()->forEmployee($adminEmployee)->create();

        $this->actingAs($adminUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/recommend", [
                'recommended_by' => $adminEmployee->id,
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Approve
    // -------------------------------------------------------------------------

    public function test_school_head_can_approve_pending_leave_request(): void
    {
        $employee = Employee::factory()->create(['vacation_leave_credits' => 20]);
        $leave    = LeaveRequest::factory()->forEmployee($employee)->create([
            'days_requested' => 2,
        ]);

        [$headUser, $headEmployee] = $this->userWithEmployee('School Head');

        $this->actingAs($headUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/approve", [
                'approved_by' => $headEmployee->id,
                'remarks'     => 'Approved.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('leave_requests', [
            'id'     => $leave->id,
            'status' => 'Approved',
        ]);
    }

    public function test_school_head_cannot_approve_own_leave_request(): void
    {
        [$headUser, $headEmployee] = $this->userWithEmployee('School Head');
        $leave = LeaveRequest::factory()->forEmployee($headEmployee)->create();

        $this->actingAs($headUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/approve", [
                'approved_by' => $headEmployee->id,
            ])
            ->assertStatus(403);
    }

    public function test_teacher_cannot_approve_leave_request(): void
    {
        $employee = Employee::factory()->create();
        $leave    = LeaveRequest::factory()->forEmployee($employee)->create();

        $teacher = $this->userWithRole('Teacher/Staff');

        $this->actingAs($teacher)
            ->putJson("/api/leave-requests/{$leave->uuid}/approve", [])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Disapprove
    // -------------------------------------------------------------------------

    public function test_school_head_can_disapprove_leave_request(): void
    {
        $employee = Employee::factory()->create();
        $leave    = LeaveRequest::factory()->forEmployee($employee)->create();

        [$headUser, $headEmployee] = $this->userWithEmployee('School Head');

        $this->actingAs($headUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/disapprove", [
                'disapproved_by' => $headEmployee->id,
                'reason'         => 'Manpower shortage during the period.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Disapproved');
    }

    public function test_disapprove_requires_reason(): void
    {
        $employee = Employee::factory()->create();
        $leave    = LeaveRequest::factory()->forEmployee($employee)->create();

        [$headUser, $headEmployee] = $this->userWithEmployee('School Head');

        $this->actingAs($headUser)
            ->putJson("/api/leave-requests/{$leave->uuid}/disapprove", [
                'disapproved_by' => $headEmployee->id,
                // missing 'reason'
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['reason']]);
    }

    // -------------------------------------------------------------------------
    // Show & List
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_list_leave_requests(): void
    {
        $employee = Employee::factory()->create();
        LeaveRequest::factory()->forEmployee($employee)->count(3)->create();

        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->getJson('/api/leave-requests')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_unknown_leave_request_returns_404(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->getJson('/api/leave-requests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_leave_requests(): void
    {
        $this->getJson('/api/leave-requests')->assertStatus(401);
    }
}
