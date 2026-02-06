<?php

namespace App\Policies;

use App\Models\ServiceCredit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceCreditPolicy
{
    /**
     * Determine if the user can view any service credits.
     */
    public function viewAny(User $user): Response
    {
        return $user->can('view_service_credits')
            ? Response::allow()
            : Response::deny('You do not have permission to view service credits.');
    }

    /**
     * Determine if the user can view a specific service credit.
     */
    public function view(User $user, ServiceCredit $serviceCredit): Response
    {
        // Can view if has permission
        if ($user->can('view_service_credits')) {
            return Response::allow();
        }

        // Can view own service credits (if it's for their employee record)
        if ($serviceCredit->employee && $serviceCredit->employee->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this service credit.');
    }

    /**
     * Determine if the user can create service credits.
     */
    public function create(User $user): Response
    {
        return $user->can('create_service_credits')
            ? Response::allow()
            : Response::deny('You do not have permission to create service credits.');
    }

    /**
     * Determine if the user can update a service credit.
     */
    public function update(User $user, ServiceCredit $serviceCredit): Response
    {
        // Cannot edit approved, rejected, or expired credits
        if (in_array($serviceCredit->status, ['Approved', 'Rejected', 'Expired'])) {
            return Response::deny('Cannot edit service credit in current status.');
        }

        // Must have create permission to edit
        return $user->can('create_service_credits')
            ? Response::allow()
            : Response::deny('You do not have permission to edit service credits.');
    }

    /**
     * Determine if the user can delete a service credit.
     */
    public function delete(User $user, ServiceCredit $serviceCredit): Response
    {
        // Cannot delete if already approved or has been used
        if ($serviceCredit->status === 'Approved') {
            return Response::deny('Cannot delete approved service credits.');
        }

        if ($serviceCredit->credits_used > 0) {
            return Response::deny('Cannot delete service credit that has been used.');
        }

        // Must have create permission to delete
        return $user->can('create_service_credits')
            ? Response::allow()
            : Response::deny('You do not have permission to delete service credits.');
    }

    /**
     * Determine if the user can approve/reject service credits.
     */
    public function approve(User $user, ServiceCredit $serviceCredit): Response
    {
        // Must have approve permission
        if (!$user->can('approve_service_credits')) {
            return Response::deny('You do not have permission to approve service credits.');
        }

        // Can only approve pending credits
        if ($serviceCredit->status !== 'Pending') {
            return Response::deny('Can only approve pending service credits.');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can apply service credit offset.
     */
    public function applyOffset(User $user): Response
    {
        return $user->can('apply_service_credit_offset')
            ? Response::allow()
            : Response::deny('You do not have permission to apply service credit offsets.');
    }
}
