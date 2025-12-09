<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\LeaveRequestController;
use App\Http\Controllers\Api\HR\ServiceRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [LogoutController::class, 'logout']);

    // HR Management - Employees
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->middleware('permission:view_employees');
        Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:create_employees');
        Route::get('/search', [EmployeeController::class, 'search'])->middleware('permission:view_employees');
        Route::get('/statistics', [EmployeeController::class, 'statistics'])->middleware('permission:view_employees');
        Route::get('/{id}', [EmployeeController::class, 'show'])->middleware('permission:view_employees');
        Route::put('/{id}', [EmployeeController::class, 'update'])->middleware('permission:edit_employees');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:delete_employees');
        Route::post('/{id}/promote', [EmployeeController::class, 'promote'])->middleware('permission:promote_employees');
    });

    // HR Management - Leave Requests
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->middleware('permission:view_leave_requests');
        Route::post('/', [LeaveRequestController::class, 'store']); // All authenticated users can create
        Route::get('/pending', [LeaveRequestController::class, 'pending'])->middleware('permission:view_leave_requests');
        Route::get('/statistics', [LeaveRequestController::class, 'statistics']);
        Route::get('/{id}', [LeaveRequestController::class, 'show'])->middleware('permission:view_leave_requests');
        Route::put('/{id}/recommend', [LeaveRequestController::class, 'recommend'])->middleware('permission:recommend_leave');
        Route::put('/{id}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:approve_leave');
        Route::put('/{id}/disapprove', [LeaveRequestController::class, 'disapprove'])->middleware('permission:approve_leave');
        Route::put('/{id}/cancel', [LeaveRequestController::class, 'cancel']);
    });

    // HR Management - Service Records
    Route::prefix('service-records')->group(function () {
        Route::get('/employee/{employeeId}', [ServiceRecordController::class, 'byEmployee'])->middleware('permission:view_service_records');
        Route::post('/', [ServiceRecordController::class, 'store'])->middleware('permission:create_service_records');
        Route::get('/{id}', [ServiceRecordController::class, 'show'])->middleware('permission:view_service_records');
        Route::put('/{id}', [ServiceRecordController::class, 'update'])->middleware('permission:edit_service_records');
        Route::delete('/{id}', [ServiceRecordController::class, 'destroy'])->middleware('permission:delete_service_records');
    });

    // Additional modules can be added here:
    // - Inventory Management
    // - Financial Management
    // - etc.
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'AdminSuite API is running',
        'timestamp' => now()->toISOString(),
    ]);
});
