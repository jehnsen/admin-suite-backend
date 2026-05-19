<?php

namespace Tests\Feature\Procurement;

use App\Models\PurchaseRequest;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_create_purchase_request(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->postJson('/api/purchase-requests', [
                'purpose'          => 'Procurement of office supplies for Q1',
                'fund_source'      => 'MOOE',
                'procurement_mode' => 'Shopping',
                'estimated_budget' => 15000.00,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.fund_source', 'MOOE');
    }

    public function test_pr_requires_purpose_and_fund_source(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->postJson('/api/purchase-requests', [
                'procurement_mode' => 'Shopping',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['purpose', 'fund_source']]);
    }

    public function test_teacher_cannot_create_purchase_request(): void
    {
        $teacher = $this->userWithRole('Teacher/Staff');

        $this->actingAs($teacher)
            ->postJson('/api/purchase-requests', [
                'purpose'     => 'Test PR',
                'fund_source' => 'MOOE',
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_purchase_request(): void
    {
        $this->postJson('/api/purchase-requests', [
            'purpose'     => 'Test',
            'fund_source' => 'MOOE',
        ])->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Show & List
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_list_purchase_requests(): void
    {
        PurchaseRequest::factory()->count(3)->create();

        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/purchase-requests')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_officer_can_view_purchase_request(): void
    {
        $pr    = PurchaseRequest::factory()->create();
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson("/api/purchase-requests/{$pr->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $pr->uuid);
    }

    public function test_unknown_purchase_request_returns_404(): void
    {
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/purchase-requests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Workflow: Draft → Submitted → Recommended → Approved
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_submit_draft_purchase_request(): void
    {
        $pr    = PurchaseRequest::factory()->draft()->create();
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->putJson("/api/purchase-requests/{$pr->uuid}/submit")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Submitted');

        $this->assertDatabaseHas('purchase_requests', [
            'id'     => $pr->id,
            'status' => 'Submitted',
        ]);
    }

    public function test_admin_officer_can_recommend_submitted_purchase_request(): void
    {
        $pr    = PurchaseRequest::factory()->submitted()->create();
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->putJson("/api/purchase-requests/{$pr->uuid}/recommend", [
                'remarks' => 'Recommend for approval.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Recommended');
    }

    public function test_school_head_can_approve_recommended_purchase_request(): void
    {
        $pr = PurchaseRequest::factory()->create([
            'status'           => 'Recommended',
            'procurement_mode' => 'Direct Contracting',
        ]);
        $head = $this->userWithRole('School Head');

        $this->actingAs($head)
            ->putJson("/api/purchase-requests/{$pr->uuid}/approve", [
                'remarks' => 'Approved.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('purchase_requests', [
            'id'     => $pr->id,
            'status' => 'Approved',
        ]);
    }

    public function test_school_head_can_disapprove_purchase_request(): void
    {
        $pr   = PurchaseRequest::factory()->submitted()->create();
        $head = $this->userWithRole('School Head');

        $this->actingAs($head)
            ->putJson("/api/purchase-requests/{$pr->uuid}/disapprove", [
                'reason' => 'Insufficient budget allocation.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'Disapproved');
    }

    public function test_disapprove_requires_reason(): void
    {
        $pr   = PurchaseRequest::factory()->submitted()->create();
        $head = $this->userWithRole('School Head');

        $this->actingAs($head)
            ->putJson("/api/purchase-requests/{$pr->uuid}/disapprove", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['reason']]);
    }

    // -------------------------------------------------------------------------
    // Pending list
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_view_pending_purchase_requests(): void
    {
        PurchaseRequest::factory()->submitted()->count(2)->create();
        PurchaseRequest::factory()->draft()->count(3)->create();

        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->getJson('/api/purchase-requests/pending')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // -------------------------------------------------------------------------
    // Update & Delete
    // -------------------------------------------------------------------------

    public function test_admin_officer_can_update_draft_purchase_request(): void
    {
        $pr    = PurchaseRequest::factory()->draft()->create();
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->putJson("/api/purchase-requests/{$pr->uuid}", [
                'purpose'     => 'Updated purpose for the PR',
                'fund_source' => 'SEF',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.purpose', 'Updated purpose for the PR')
            ->assertJsonPath('data.fund_source', 'SEF');
    }

    public function test_admin_officer_can_delete_draft_purchase_request(): void
    {
        $pr    = PurchaseRequest::factory()->draft()->create();
        $admin = $this->userWithRole('Admin Officer');

        $this->actingAs($admin)
            ->deleteJson("/api/purchase-requests/{$pr->uuid}")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Purchase request deleted successfully.']);

        $this->assertSoftDeleted('purchase_requests', ['id' => $pr->id]);
    }

    public function test_teacher_cannot_delete_purchase_request(): void
    {
        $pr      = PurchaseRequest::factory()->draft()->create();
        $teacher = $this->userWithRole('Teacher/Staff');

        $this->actingAs($teacher)
            ->deleteJson("/api/purchase-requests/{$pr->uuid}")
            ->assertStatus(403);
    }
}
