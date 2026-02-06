<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\LeaveRequestController;
use App\Http\Controllers\Api\HR\ServiceRecordController;
use App\Http\Controllers\Api\HR\TrainingController;
use App\Http\Controllers\Api\Procurement\SupplierController;
use App\Http\Controllers\Api\Procurement\PurchaseRequestController;
use App\Http\Controllers\Api\Procurement\QuotationController;
use App\Http\Controllers\Api\Procurement\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\DeliveryController;
use App\Http\Controllers\Api\Procurement\DeliveryAssetTaggingController;
use App\Http\Controllers\Api\Inventory\InventoryItemController;
use App\Http\Controllers\Api\Inventory\StockCardController;
use App\Http\Controllers\Api\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Api\Inventory\PhysicalCountController;
use App\Http\Controllers\Api\Financial\BudgetController;
use App\Http\Controllers\Api\Financial\CashAdvanceController;
use App\Http\Controllers\Api\Financial\DisbursementController;
use App\Http\Controllers\Api\Financial\LiquidationController;
use App\Http\Controllers\Api\Financial\TransactionController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\Shared\DocumentController;
use App\Http\Controllers\Api\Shared\AuditController;
use App\Http\Controllers\Api\Admin\UserManagementController;

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
    // DISABLED: Public registration not allowed in production
    // Admin Officer creates user accounts via /users endpoint
    // Route::post('/register', [RegisterController::class, 'register']);

    Route::post('/login', [LoginController::class, 'login']);
});

// Protected routes (require authentication only)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [LogoutController::class, 'logout']);

    // User Profile / My Account
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/change-password', [ProfileController::class, 'changePassword']);
        Route::get('/statistics', [ProfileController::class, 'statistics']);
        Route::get('/activities', [ProfileController::class, 'activities']);
    });

    // Alias routes for My Account
    Route::prefix('my-account')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/change-password', [ProfileController::class, 'changePassword']);
        Route::get('/statistics', [ProfileController::class, 'statistics']);
        Route::get('/activities', [ProfileController::class, 'activities']);
    });

    // Admin - User Management (Admin Officer only)
    Route::prefix('users')->middleware('permission:view_users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::get('/statistics', [UserManagementController::class, 'statistics']);
        Route::get('/{id}', [UserManagementController::class, 'show']);

        Route::post('/', [UserManagementController::class, 'store'])->middleware('permission:create_users');
        Route::put('/{id}', [UserManagementController::class, 'update'])->middleware('permission:edit_users');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->middleware('permission:delete_users');
        Route::post('/{id}/reset-password', [UserManagementController::class, 'resetPassword'])->middleware('permission:reset_user_password');
        Route::post('/{id}/assign-role', [UserManagementController::class, 'assignRole'])->middleware('permission:manage_user_roles');
    });

    // HR Management - Employees
    Route::prefix('employees')->middleware('permission:view_employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/search', [EmployeeController::class, 'search']);
        Route::get('/statistics', [EmployeeController::class, 'statistics']);
        Route::get('/{id}', [EmployeeController::class, 'show']);

        Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:create_employees');
        Route::put('/{id}', [EmployeeController::class, 'update'])->middleware('permission:edit_employees');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:delete_employees');
        Route::post('/{id}/promote', [EmployeeController::class, 'promote'])->middleware('permission:promote_employees');
        Route::post('/update-monthly-leave-credits', [EmployeeController::class, 'updateMonthlyLeaveCredits'])->middleware('permission:manage_leave_credits');
    });

    // HR Management - Leave Requests
    Route::prefix('leave-requests')->middleware('permission:view_leave_requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::get('/pending', [LeaveRequestController::class, 'pending']);
        Route::get('/statistics', [LeaveRequestController::class, 'statistics']);
        Route::get('/{id}', [LeaveRequestController::class, 'show']);

        Route::post('/', [LeaveRequestController::class, 'store'])->middleware('permission:create_leave_request');
        Route::put('/{id}/recommend', [LeaveRequestController::class, 'recommend'])->middleware('permission:recommend_leave');
        Route::put('/{id}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:approve_leave');
        Route::put('/{id}/disapprove', [LeaveRequestController::class, 'disapprove'])->middleware('permission:reject_leave');
        Route::put('/{id}/cancel', [LeaveRequestController::class, 'cancel']); // No extra permission - policy handles this
    });

    // HR Management - Service Records
    Route::prefix('service-records')->middleware('permission:view_service_records')->group(function () {
        Route::get('/employee/{employeeId}', [ServiceRecordController::class, 'byEmployee']);
        Route::get('/{id}', [ServiceRecordController::class, 'show']);

        Route::post('/', [ServiceRecordController::class, 'store'])->middleware('permission:create_service_records');
        Route::put('/{id}', [ServiceRecordController::class, 'update'])->middleware('permission:edit_service_records');
        Route::delete('/{id}', [ServiceRecordController::class, 'destroy'])->middleware('permission:delete_service_records');
    });

    // HR Management - Trainings
    Route::prefix('trainings')->group(function () {
        Route::get('/', [TrainingController::class, 'index']);
        Route::post('/', [TrainingController::class, 'store']);
        Route::get('/statistics', [TrainingController::class, 'statistics']);
        Route::get('/type/{type}', [TrainingController::class, 'byType']);
        Route::get('/year/{year}', [TrainingController::class, 'byYear']);
        Route::get('/employee/{employeeId}', [TrainingController::class, 'byEmployee']);
        Route::get('/employee/{employeeId}/completed', [TrainingController::class, 'completedByEmployee']);
        Route::get('/{id}', [TrainingController::class, 'show']);
        Route::put('/{id}', [TrainingController::class, 'update']);
        Route::delete('/{id}', [TrainingController::class, 'destroy']);
    });

    // Procurement - Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/active', [SupplierController::class, 'active']);
        Route::get('/search', [SupplierController::class, 'search']);
        Route::get('/statistics', [SupplierController::class, 'statistics']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
    });

    // Procurement - Purchase Requests
    Route::prefix('purchase-requests')->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index']);
        Route::post('/', [PurchaseRequestController::class, 'store']);
        Route::get('/pending', [PurchaseRequestController::class, 'pending']);
        Route::get('/statistics', [PurchaseRequestController::class, 'statistics']);
        Route::get('/{id}', [PurchaseRequestController::class, 'show']);
        Route::put('/{id}', [PurchaseRequestController::class, 'update']);
        Route::delete('/{id}', [PurchaseRequestController::class, 'destroy']);
        Route::put('/{id}/submit', [PurchaseRequestController::class, 'submit']);
        Route::put('/{id}/recommend', [PurchaseRequestController::class, 'recommend']);
        Route::put('/{id}/approve', [PurchaseRequestController::class, 'approve']);
        Route::put('/{id}/disapprove', [PurchaseRequestController::class, 'disapprove']);
        Route::put('/{id}/cancel', [PurchaseRequestController::class, 'cancel']);
    });

    // Procurement - Quotations
    Route::prefix('quotations')->group(function () {
        Route::get('/', [QuotationController::class, 'index']);
        Route::post('/', [QuotationController::class, 'store']);
        Route::get('/purchase-request/{prId}', [QuotationController::class, 'byPurchaseRequest']);
        Route::get('/{id}', [QuotationController::class, 'show']);
        Route::put('/{id}', [QuotationController::class, 'update']);
        Route::delete('/{id}', [QuotationController::class, 'destroy']);
        Route::put('/{id}/select', [QuotationController::class, 'select']);
        Route::put('/purchase-request/{prId}/evaluate', [QuotationController::class, 'evaluate']);
    });

    // Procurement - Purchase Orders
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::get('/pending', [PurchaseOrderController::class, 'pending']);
        Route::get('/statistics', [PurchaseOrderController::class, 'statistics']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::put('/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
        Route::put('/{id}/approve', [PurchaseOrderController::class, 'approve']);
        Route::put('/{id}/send-to-supplier', [PurchaseOrderController::class, 'sendToSupplier']);
        Route::put('/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    // Procurement - Deliveries
    Route::prefix('deliveries')->group(function () {
        Route::get('/', [DeliveryController::class, 'index']);
        Route::post('/', [DeliveryController::class, 'store']);
        Route::get('/pending', [DeliveryController::class, 'pending']);
        Route::get('/statistics', [DeliveryController::class, 'statistics']);
        Route::get('/purchase-order/{poId}', [DeliveryController::class, 'byPurchaseOrder']);
        Route::get('/{id}', [DeliveryController::class, 'show']);
        Route::put('/{id}', [DeliveryController::class, 'update']);
        Route::delete('/{id}', [DeliveryController::class, 'destroy']);
        Route::put('/{id}/inspect', [DeliveryController::class, 'inspect']);
        Route::put('/{id}/accept', [DeliveryController::class, 'accept']);
        Route::put('/{id}/reject', [DeliveryController::class, 'reject']);
        Route::get('/{id}/pending-assets', [DeliveryAssetTaggingController::class, 'getPendingAssets']);
        Route::post('/{id}/tag-assets', [DeliveryAssetTaggingController::class, 'tagAssets']);
    });

    // Inventory Management - Inventory Items
    Route::prefix('inventory-items')->middleware('permission:view_inventory')->group(function () {
        Route::get('/', [InventoryItemController::class, 'index']);
        Route::get('/search', [InventoryItemController::class, 'search']);
        Route::get('/with-balances', [InventoryItemController::class, 'withBalances']);
        Route::get('/low-stock', [InventoryItemController::class, 'lowStock']);
        Route::get('/statistics', [InventoryItemController::class, 'statistics']);
        Route::get('/{id}', [InventoryItemController::class, 'show']);
        Route::get('/{id}/with-balance', [InventoryItemController::class, 'showWithBalance']);

        Route::post('/', [InventoryItemController::class, 'store'])->middleware('permission:create_inventory');
        Route::put('/{id}', [InventoryItemController::class, 'update'])->middleware('permission:edit_inventory');
        Route::delete('/{id}', [InventoryItemController::class, 'destroy'])->middleware('permission:delete_inventory');
    });

    // Inventory Management - Stock Cards
    Route::prefix('stock-cards')->middleware('permission:view_inventory')->group(function () {
        Route::get('/', [StockCardController::class, 'index']);
        Route::get('/{id}', [StockCardController::class, 'show']);
        Route::get('/item/{itemId}', [StockCardController::class, 'byInventoryItem']);
        Route::get('/item/{itemId}/balance', [StockCardController::class, 'currentBalance']);

        Route::post('/stock-in', [StockCardController::class, 'stockIn'])->middleware('permission:create_inventory');
        Route::post('/stock-out', [StockCardController::class, 'stockOut'])->middleware('permission:issue_inventory');
        Route::post('/donation', [StockCardController::class, 'recordDonation'])->middleware('permission:create_inventory');
    });

    // Inventory Management - Inventory Adjustments
    Route::prefix('inventory-adjustments')->middleware('permission:view_inventory')->group(function () {
        Route::get('/', [InventoryAdjustmentController::class, 'index']);
        Route::get('/pending', [InventoryAdjustmentController::class, 'pending']);
        Route::get('/{id}', [InventoryAdjustmentController::class, 'show']);

        Route::post('/', [InventoryAdjustmentController::class, 'store'])->middleware('permission:create_inventory');
        Route::put('/{id}', [InventoryAdjustmentController::class, 'update'])->middleware('permission:edit_inventory');
        Route::delete('/{id}', [InventoryAdjustmentController::class, 'destroy'])->middleware('permission:delete_inventory');
        Route::put('/{id}/approve', [InventoryAdjustmentController::class, 'approve'])->middleware('permission:edit_inventory');
        Route::put('/{id}/reject', [InventoryAdjustmentController::class, 'reject'])->middleware('permission:edit_inventory');
    });

    // Inventory Management - Physical Counts
    Route::prefix('physical-counts')->middleware('permission:view_inventory')->group(function () {
        Route::get('/', [PhysicalCountController::class, 'index']);
        Route::get('/with-variances', [PhysicalCountController::class, 'withVariances']);
        Route::get('/{id}', [PhysicalCountController::class, 'show']);

        Route::post('/', [PhysicalCountController::class, 'store'])->middleware('permission:create_inventory');
        Route::put('/{id}', [PhysicalCountController::class, 'update'])->middleware('permission:edit_inventory');
        Route::delete('/{id}', [PhysicalCountController::class, 'destroy'])->middleware('permission:delete_inventory');
    });

    // Financial Management - Budgets
    Route::prefix('budgets')->middleware('permission:view_budget')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::get('/active', [BudgetController::class, 'active']);
        Route::get('/utilization', [BudgetController::class, 'utilization']);
        Route::get('/nearly-depleted', [BudgetController::class, 'nearlyDepleted']);
        Route::get('/statistics', [BudgetController::class, 'statistics']);
        Route::get('/fiscal-year/{year}', [BudgetController::class, 'byFiscalYear']);
        Route::get('/fund-source/{fundSource}', [BudgetController::class, 'byFundSource']);
        Route::get('/{id}', [BudgetController::class, 'show']);

        Route::post('/', [BudgetController::class, 'store'])->middleware('permission:create_budget');
        Route::put('/{id}', [BudgetController::class, 'update'])->middleware('permission:edit_budget');
        Route::delete('/{id}', [BudgetController::class, 'destroy'])->middleware('permission:edit_budget');
        Route::put('/{id}/approve', [BudgetController::class, 'approve'])->middleware('permission:approve_budget');
        Route::put('/{id}/activate', [BudgetController::class, 'activate'])->middleware('permission:approve_budget');
        Route::put('/{id}/close', [BudgetController::class, 'close'])->middleware('permission:approve_budget');
        Route::put('/{id}/update-utilization', [BudgetController::class, 'updateUtilization'])->middleware('permission:edit_budget');
    });

    // Budget Allocations (Alias for Budgets - for frontend compatibility)
    Route::prefix('budget-allocations')->middleware('permission:view_budget')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::get('/active', [BudgetController::class, 'active']);
        Route::get('/utilization', [BudgetController::class, 'utilization']);
        Route::get('/statistics', [BudgetController::class, 'statistics']);
        Route::get('/{id}', [BudgetController::class, 'show']);

        Route::post('/', [BudgetController::class, 'store'])->middleware('permission:create_budget');
        Route::put('/{id}', [BudgetController::class, 'update'])->middleware('permission:edit_budget');
        Route::delete('/{id}', [BudgetController::class, 'destroy'])->middleware('permission:edit_budget');
    });

    // Financial Management - Cash Advances
    Route::prefix('cash-advances')->middleware('permission:view_expenses')->group(function () {
        Route::get('/', [CashAdvanceController::class, 'index']);
        Route::get('/pending', [CashAdvanceController::class, 'pending']);
        Route::get('/overdue', [CashAdvanceController::class, 'overdue']);
        Route::get('/statistics', [CashAdvanceController::class, 'statistics']);
        Route::get('/employee/{employeeId}', [CashAdvanceController::class, 'byEmployee']);
        Route::get('/{id}', [CashAdvanceController::class, 'show']);

        Route::post('/', [CashAdvanceController::class, 'store'])->middleware('permission:create_expense');
        Route::put('/{id}', [CashAdvanceController::class, 'update'])->middleware('permission:create_expense');
        Route::delete('/{id}', [CashAdvanceController::class, 'destroy'])->middleware('permission:create_expense');
        Route::put('/{id}/approve', [CashAdvanceController::class, 'approve'])->middleware('permission:approve_expense');
        Route::put('/{id}/release', [CashAdvanceController::class, 'release'])->middleware('permission:approve_expense');
    });

    // Financial Management - Disbursements
    Route::prefix('disbursements')->middleware('permission:view_expenses')->group(function () {
        Route::get('/', [DisbursementController::class, 'index']);
        Route::get('/pending', [DisbursementController::class, 'pending']);
        Route::get('/statistics', [DisbursementController::class, 'statistics']);
        Route::get('/{id}', [DisbursementController::class, 'show']);

        Route::post('/', [DisbursementController::class, 'store'])->middleware('permission:create_expense');
        Route::put('/{id}', [DisbursementController::class, 'update'])->middleware('permission:create_expense');
        Route::delete('/{id}', [DisbursementController::class, 'destroy'])->middleware('permission:create_expense');
        Route::put('/{id}/certify', [DisbursementController::class, 'certify'])->middleware('permission:approve_expense');
        Route::put('/{id}/approve', [DisbursementController::class, 'approve'])->middleware('permission:approve_expense');
        Route::put('/{id}/mark-paid', [DisbursementController::class, 'markPaid'])->middleware('permission:approve_expense');
    });

    // Financial Management - Liquidations
    Route::prefix('liquidations')->middleware('permission:view_expenses')->group(function () {
        Route::get('/', [LiquidationController::class, 'index']);
        Route::get('/pending', [LiquidationController::class, 'pending']);
        Route::get('/cash-advance/{caId}', [LiquidationController::class, 'byCashAdvance']);
        Route::get('/{id}', [LiquidationController::class, 'show']);

        Route::post('/', [LiquidationController::class, 'store'])->middleware('permission:create_expense');
        Route::put('/{id}', [LiquidationController::class, 'update'])->middleware('permission:create_expense');
        Route::delete('/{id}', [LiquidationController::class, 'destroy'])->middleware('permission:create_expense');
        Route::post('/{id}/items', [LiquidationController::class, 'addItem'])->middleware('permission:create_expense');
        Route::put('/{id}/approve', [LiquidationController::class, 'approve'])->middleware('permission:approve_expense');
        Route::put('/{id}/reject', [LiquidationController::class, 'reject'])->middleware('permission:approve_expense');
    });

    // Financial Management - Transactions
    Route::prefix('transactions')->middleware('permission:view_expenses')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/recent', [TransactionController::class, 'recent']);
        Route::get('/statistics', [TransactionController::class, 'statistics']);
        Route::get('/{id}', [TransactionController::class, 'show']);

        Route::post('/', [TransactionController::class, 'store'])->middleware('permission:create_expense');
        Route::put('/{id}', [TransactionController::class, 'update'])->middleware('permission:create_expense');
        Route::delete('/{id}', [TransactionController::class, 'destroy'])->middleware('permission:create_expense');
        Route::put('/{id}/verify', [TransactionController::class, 'verify'])->middleware('permission:approve_expense');
    });

    // Shared Module - Document Management
    Route::prefix('documents')->group(function () {
        Route::post('/upload', [DocumentController::class, 'upload']);
        Route::get('/', [DocumentController::class, 'index']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::get('/{id}/download', [DocumentController::class, 'download'])
            ->middleware('signed')  // Require signed URL for download (security for sensitive docs)
            ->name('documents.download');
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
    });

    // Shared Module - Audit Trail
    Route::prefix('audit')->group(function () {
        Route::get('/logs', [AuditController::class, 'index']);
        Route::get('/entity-history', [AuditController::class, 'entityHistory']);
        Route::get('/report', [AuditController::class, 'report']);
        Route::get('/my-activity', [AuditController::class, 'myActivity']);
        Route::get('/module/{module}', [AuditController::class, 'byModule']);
        Route::get('/export', [AuditController::class, 'export']);
        Route::get('/{id}', [AuditController::class, 'show']);
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'AdminSuite API is running',
        'timestamp' => now()->toISOString(),
    ]);
});
