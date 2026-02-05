<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * @group User Management
 *
 * Admin-only endpoints for managing user accounts.
 * Only Admin Officer and Super Admin can create/manage users.
 */
class UserManagementController extends Controller
{
    /**
     * List Users
     *
     * Get paginated list of all users with their roles.
     *
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam search string Search by name or email. Example: Juan
     * @queryParam role string Filter by role name. Example: Teacher/Staff
     *
     * @response 200 {
     *   "data": [{
     *     "id": 1,
     *     "name": "Juan dela Cruz",
     *     "email": "juan@deped.gov.ph",
     *     "roles": ["Teacher/Staff"],
     *     "created_at": "2024-01-15T10:00:00.000000Z"
     *   }],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 50
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'employee_id' => $user->employee_id,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

        return response()->json($users);
    }

    /**
     * Create User
     *
     * Create a new user account with specified role.
     * Admin Officer can create Teacher/Staff and Admin Officer accounts.
     * Super Admin can create any account including Super Admin.
     *
     * @bodyParam name string required Full name. Example: Maria Santos
     * @bodyParam email string required Email address (must be @deped.gov.ph). Example: maria.santos@deped.gov.ph
     * @bodyParam password string optional Password (min 8 characters). If not provided, random password is generated. Example: SecurePass123!
     * @bodyParam role string required Role to assign. Example: Teacher/Staff
     * @bodyParam employee_id integer optional Link to employee record. Example: 5
     * @bodyParam send_welcome_email boolean optional Send welcome email with credentials. Example: true
     *
     * @response 201 {
     *   "message": "User created successfully",
     *   "user": {
     *     "id": 10,
     *     "name": "Maria Santos",
     *     "email": "maria.santos@deped.gov.ph",
     *     "roles": ["Teacher/Staff"]
     *   },
     *   "temporary_password": "RandomPass123"
     * }
     * @response 422 {
     *   "message": "Validation error",
     *   "errors": {}
     * }
     * @response 403 {
     *   "message": "You cannot create users with this role"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/@deped\.gov\.ph$/i', // Only DepEd emails
            ],
            'password' => ['nullable', 'string', Password::min(8)],
            'role' => 'required|string|exists:roles,name',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'send_welcome_email' => 'nullable|boolean',
        ]);

        // Authorization: Check if user can create accounts with this role
        $role = Role::findByName($validated['role']);

        // Prevent non-Super Admins from creating Super Admin accounts
        if ($validated['role'] === 'Super Admin' && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'Only Super Admin can create Super Admin accounts',
            ], 403);
        }

        // Generate password if not provided
        $generatedPassword = null;
        if (empty($validated['password'])) {
            $generatedPassword = Str::random(12);
            $validated['password'] = $generatedPassword;
        }

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'employee_id' => $validated['employee_id'] ?? null,
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        // TODO: Send welcome email if requested
        // if ($validated['send_welcome_email'] ?? false) {
        //     Mail::to($user)->send(new WelcomeEmail($user, $generatedPassword));
        // }

        $response = [
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'employee_id' => $user->employee_id,
            ],
        ];

        // Include temporary password in response if it was generated
        if ($generatedPassword) {
            $response['temporary_password'] = $generatedPassword;
            $response['message'] .= '. Please save the temporary password and share it securely with the user.';
        }

        return response()->json($response, 201);
    }

    /**
     * Show User
     *
     * Get detailed information about a specific user.
     *
     * @urlParam id integer required User ID. Example: 5
     *
     * @response 200 {
     *   "id": 5,
     *   "name": "Juan dela Cruz",
     *   "email": "juan@deped.gov.ph",
     *   "roles": ["Teacher/Staff"],
     *   "permissions": ["view_employees", "create_leave_request"],
     *   "employee_id": 3,
     *   "created_at": "2024-01-15T10:00:00.000000Z"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles', 'employee'])->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'employee_id' => $user->employee_id,
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'first_name' => $user->employee->first_name,
                'last_name' => $user->employee->last_name,
                'position' => $user->employee->position,
            ] : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Update User
     *
     * Update user information.
     *
     * @urlParam id integer required User ID. Example: 5
     * @bodyParam name string optional Full name. Example: Juan P. dela Cruz
     * @bodyParam email string optional Email address. Example: juan.delacruz@deped.gov.ph
     * @bodyParam employee_id integer optional Link to employee record. Example: 3
     *
     * @response 200 {
     *   "message": "User updated successfully",
     *   "user": {
     *     "id": 5,
     *     "name": "Juan P. dela Cruz",
     *     "email": "juan.delacruz@deped.gov.ph"
     *   }
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent editing Super Admin by non-Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'You cannot edit Super Admin accounts',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $id,
                'regex:/@deped\.gov\.ph$/i',
            ],
            'employee_id' => 'nullable|integer|exists:employees,id',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
            ],
        ]);
    }

    /**
     * Delete User
     *
     * Delete a user account.
     * Cannot delete your own account or Super Admin accounts (unless you are Super Admin).
     *
     * @urlParam id integer required User ID. Example: 5
     *
     * @response 200 {
     *   "message": "User deleted successfully"
     * }
     * @response 403 {
     *   "message": "You cannot delete this user"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Prevent deleting Super Admin by non-Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'You cannot delete Super Admin accounts',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Reset User Password
     *
     * Reset a user's password and generate a new temporary password.
     *
     * @urlParam id integer required User ID. Example: 5
     * @bodyParam password string optional New password. If not provided, random password is generated. Example: NewPass123!
     *
     * @response 200 {
     *   "message": "Password reset successfully",
     *   "temporary_password": "RandomPass456"
     * }
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent resetting Super Admin password by non-Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'You cannot reset Super Admin passwords',
            ], 403);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', Password::min(8)],
        ]);

        // Generate password if not provided
        $generatedPassword = null;
        if (empty($validated['password'])) {
            $generatedPassword = Str::random(12);
            $newPassword = $generatedPassword;
        } else {
            $newPassword = $validated['password'];
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $response = [
            'message' => 'Password reset successfully',
        ];

        if ($generatedPassword) {
            $response['temporary_password'] = $generatedPassword;
            $response['message'] .= '. Please save the temporary password and share it securely with the user.';
        }

        return response()->json($response);
    }

    /**
     * Assign Role
     *
     * Change a user's role.
     *
     * @urlParam id integer required User ID. Example: 5
     * @bodyParam role string required New role to assign. Example: Admin Officer
     *
     * @response 200 {
     *   "message": "Role assigned successfully",
     *   "user": {
     *     "id": 5,
     *     "name": "Juan dela Cruz",
     *     "roles": ["Admin Officer"]
     *   }
     * }
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Prevent non-Super Admins from creating/modifying Super Admin accounts
        if ($validated['role'] === 'Super Admin' && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'Only Super Admin can assign Super Admin role',
            ], 403);
        }

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json([
                'message' => 'You cannot modify Super Admin accounts',
            ], 403);
        }

        // Remove all existing roles and assign new one
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Get Statistics
     *
     * Get user account statistics.
     *
     * @response 200 {
     *   "total_users": 50,
     *   "by_role": {
     *     "Teacher/Staff": 40,
     *     "Admin Officer": 8,
     *     "School Head": 1,
     *     "Super Admin": 1
     *   },
     *   "recent_registrations": 5
     * }
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();

        $byRole = Role::withCount('users')
            ->get()
            ->pluck('users_count', 'name')
            ->toArray();

        $recentRegistrations = User::where('created_at', '>=', now()->subDays(30))->count();

        return response()->json([
            'total_users' => $totalUsers,
            'by_role' => $byRole,
            'recent_registrations' => $recentRegistrations,
        ]);
    }
}
