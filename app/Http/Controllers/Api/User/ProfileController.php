<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['employee']);

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'employee' => $user->employee,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update authenticated user's profile
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            ]);

            $user = $request->user();
            $user->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully.',
                'data' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = $request->user();

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.'
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'message' => 'Password changed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'data' => [
                        'message' => 'No employee record linked to this account.'
                    ]
                ]);
            }

            $stats = [
                'leave_requests' => [
                    'total' => $employee->leaveRequests()->count(),
                    'pending' => $employee->leaveRequests()->where('status', 'Pending')->count(),
                    'approved' => $employee->leaveRequests()->where('status', 'Approved')->count(),
                ],
                'cash_advances' => [
                    'total' => \App\Models\CashAdvance::where('employee_id', $employee->id)->count(),
                    'pending' => \App\Models\CashAdvance::where('employee_id', $employee->id)->where('status', 'Pending')->count(),
                    'unliquidated' => \App\Models\CashAdvance::where('employee_id', $employee->id)
                        ->whereIn('status', ['Released', 'Partially Liquidated'])->count(),
                ],
                'trainings' => [
                    'total' => \App\Models\Training::where('employee_id', $employee->id)->count(),
                    'completed' => \App\Models\Training::where('employee_id', $employee->id)
                        ->where('status', 'Completed')->count(),
                ],
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's recent activities
     */
    public function activities(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (!$employee) {
                return response()->json([
                    'data' => []
                ]);
            }

            $perPage = $request->input('per_page', 10);

            // Get recent leave requests
            $leaveRequests = $employee->leaveRequests()
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'leave_request',
                        'description' => "Leave request: {$item->leave_type}",
                        'status' => $item->status,
                        'date' => $item->created_at,
                    ];
                });

            // Get recent cash advances
            $cashAdvances = \App\Models\CashAdvance::where('employee_id', $employee->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'cash_advance',
                        'description' => "Cash advance: {$item->purpose}",
                        'status' => $item->status,
                        'date' => $item->created_at,
                    ];
                });

            // Combine and sort by date
            $activities = $leaveRequests->concat($cashAdvances)
                ->sortByDesc('date')
                ->take($perPage)
                ->values();

            return response()->json(['data' => $activities]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
