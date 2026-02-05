<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine if user can view any employees.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to view employees.');
    }

    /**
     * Determine if user can view a specific employee.
     */
    public function view(User $user, Employee $employee): Response
    {
        return $user->hasPermissionTo('view_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to view employee details.');
    }

    /**
     * Determine if user can create employees.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to create employees.');
    }

    /**
     * Determine if user can update employees.
     */
    public function update(User $user, Employee $employee): Response
    {
        return $user->hasPermissionTo('edit_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to edit employees.');
    }

    /**
     * Determine if user can delete employees.
     */
    public function delete(User $user, Employee $employee): Response
    {
        return $user->hasPermissionTo('delete_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to delete employees.');
    }

    /**
     * Determine if user can promote employees.
     */
    public function promote(User $user, Employee $employee): Response
    {
        return $user->hasPermissionTo('promote_employees')
            ? Response::allow()
            : Response::deny('You do not have permission to promote employees.');
    }
}
