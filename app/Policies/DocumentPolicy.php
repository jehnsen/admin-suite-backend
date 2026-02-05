<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine if user can view the document.
     * Users can view documents they uploaded OR documents attached to entities they have access to.
     */
    public function view(User $user, Document $document): Response
    {
        // Super Admin can view all
        if ($user->hasRole('Super Admin')) {
            return Response::allow();
        }

        // User uploaded the document
        if ($document->uploaded_by === $user->id) {
            return Response::allow();
        }

        // Check if user has access to the parent entity
        if ($this->canAccessParentEntity($user, $document)) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this document.');
    }

    /**
     * Determine if user can delete the document.
     */
    public function delete(User $user, Document $document): Response
    {
        // Super Admin and Admin Officer can delete any
        if ($user->hasAnyRole(['Super Admin', 'Admin Officer'])) {
            return Response::allow();
        }

        // User can delete their own uploads
        if ($document->uploaded_by === $user->id) {
            return Response::allow();
        }

        return Response::deny('You can only delete documents you uploaded.');
    }

    /**
     * Check if user has access to the parent documentable entity.
     */
    private function canAccessParentEntity(User $user, Document $document): bool
    {
        $entityType = class_basename($document->documentable_type);
        $entity = $document->documentable;

        if (!$entity) {
            return false;
        }

        // Business logic: Determine access based on entity type
        return match ($entityType) {
            'LeaveRequest' => $this->canAccessLeaveRequest($user, $entity),
            'CashAdvance' => $this->canAccessCashAdvance($user, $entity),
            'Liquidation' => $this->canAccessLiquidation($user, $entity),
            'PurchaseRequest', 'PurchaseOrder', 'Delivery' => $user->hasPermissionTo('view_inventory'),
            'InventoryAdjustment' => $user->hasPermissionTo('view_inventory'),
            default => false,
        };
    }

    /**
     * Check if user can access leave request documents.
     */
    private function canAccessLeaveRequest(User $user, $leaveRequest): bool
    {
        // User can see their own leave documents
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return true;
        }

        // School Head and Admin Officer can see all
        return $user->hasAnyRole(['Super Admin', 'School Head', 'Admin Officer']);
    }

    /**
     * Check if user can access cash advance documents.
     */
    private function canAccessCashAdvance(User $user, $cashAdvance): bool
    {
        // User can see their own cash advance documents
        if ($user->employee && $user->employee->id === $cashAdvance->employee_id) {
            return true;
        }

        // Users with view_expenses permission can see all
        return $user->hasPermissionTo('view_expenses');
    }

    /**
     * Check if user can access liquidation documents.
     */
    private function canAccessLiquidation(User $user, $liquidation): bool
    {
        // Liquidations are tied to cash advances
        if ($liquidation->cashAdvance) {
            return $this->canAccessCashAdvance($user, $liquidation->cashAdvance);
        }

        // Users with view_expenses permission can see all
        return $user->hasPermissionTo('view_expenses');
    }
}
