<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @group User Management
 *
 * Admin-only endpoints for managing user accounts.
 * Only Admin Officer and Super Admin can create/manage users.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with(['roles', 'employee']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate($this->getPerPage($request));

        return UserResource::collection($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => [
                'required', 'string', 'email', 'max:255', 'unique:users',
                'regex:/@deped\.gov\.ph$/i',
            ],
            'password'           => ['nullable', 'string', Password::min(8)],
            'role'               => 'required|string|exists:roles,name',
            'employee_id'        => 'nullable|integer|exists:employees,id',
            'send_welcome_email' => 'nullable|boolean',
        ]);

        if ($validated['role'] === 'Super Admin' && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Only Super Admin can create Super Admin accounts'], 403);
        }

        $generatedPassword = null;
        if (empty($validated['password'])) {
            $generatedPassword = Str::random(12);
            $validated['password'] = $generatedPassword;
        }

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'employee_id' => $validated['employee_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);
        $user->load(['roles', 'employee']);

        $message = 'User created successfully';
        if ($generatedPassword) {
            $message .= '. A temporary password was generated. Use the reset-password endpoint to issue a new one or implement email delivery.';
        }

        return response()->json([
            'message' => $message,
            'data'    => new UserResource($user),
        ], 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::with(['roles', 'permissions', 'employee'])->findOrFail($id);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot edit Super Admin accounts'], 403);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => [
                'sometimes', 'string', 'email', 'max:255',
                'unique:users,email,' . $id,
                'regex:/@deped\.gov\.ph$/i',
            ],
            'employee_id' => 'nullable|integer|exists:employees,id',
        ]);

        $user->update($validated);
        $user->load(['roles', 'employee']);

        return response()->json([
            'message' => 'User updated successfully',
            'data'    => new UserResource($user),
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot delete Super Admin accounts'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function resetPassword(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot reset Super Admin passwords'], 403);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string', Password::min(8)],
        ]);

        $generatedPassword = null;
        if (empty($validated['password'])) {
            $generatedPassword = Str::random(12);
            $newPassword = $generatedPassword;
        } else {
            $newPassword = $validated['password'];
        }

        $user->update(['password' => Hash::make($newPassword)]);

        $message = 'Password reset successfully';
        if ($generatedPassword) {
            $message .= '. A temporary password was generated — deliver it to the user via a secure channel (e.g., email).';
        }

        return response()->json(['message' => $message]);
    }

    public function assignRole(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validated['role'] === 'Super Admin' && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Only Super Admin can assign Super Admin role'], 403);
        }

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify Super Admin accounts'], 403);
        }

        $user->syncRoles([$validated['role']]);
        $user->load(['roles', 'employee']);

        return response()->json([
            'message' => 'Role assigned successfully',
            'data'    => new UserResource($user),
        ]);
    }

    public function listPermissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->pluck('name');

        return response()->json(['data' => $permissions]);
    }

    public function syncPermissions(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify Super Admin accounts.'], 403);
        }

        $validated = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->preventPrivilegeEscalation($validated['permissions']);

        $user->syncPermissions($validated['permissions']);

        return response()->json([
            'message'            => 'Permissions updated successfully.',
            'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
            'all_permissions'    => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function givePermissions(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify Super Admin accounts.'], 403);
        }

        $validated = $request->validate([
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->preventPrivilegeEscalation($validated['permissions']);

        $user->givePermissionTo($validated['permissions']);

        return response()->json([
            'message'            => 'Permissions granted successfully.',
            'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
            'all_permissions'    => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function revokePermissions(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\User::where('uuid', $uuid)->value('id') ?? 0;
        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify Super Admin accounts.'], 403);
        }

        $validated = $request->validate([
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        foreach ($validated['permissions'] as $permission) {
            $user->revokePermissionTo($permission);
        }

        return response()->json([
            'message'            => 'Permissions revoked successfully.',
            'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
            'all_permissions'    => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    private function preventPrivilegeEscalation(array $permissions): void
    {
        $caller = auth()->user();

        if ($caller->hasRole('Super Admin')) {
            return;
        }

        $callerPermissions = $caller->getAllPermissions()->pluck('name');
        $disallowed = array_diff($permissions, $callerPermissions->toArray());

        if (!empty($disallowed)) {
            abort(403, 'You cannot grant permissions you do not have: ' . implode(', ', $disallowed));
        }
    }

    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();

        $byRole = Role::withCount('users')
            ->get()
            ->pluck('users_count', 'name')
            ->toArray();

        $recentRegistrations = User::where('created_at', '>=', now()->subDays(30))->count();

        return response()->json([
            'total_users'          => $totalUsers,
            'by_role'              => $byRole,
            'recent_registrations' => $recentRegistrations,
        ]);
    }
}
