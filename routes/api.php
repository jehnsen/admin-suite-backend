<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\LeaveRequestController;
use App\Http\Controllers\Api\HR\ServiceRecordController;
use App\Http\Controllers\Api\Procurement\SupplierController;
use App\Http\Controllers\Api\Procurement\PurchaseRequestController;
use App\Http\Controllers\Api\Procurement\QuotationController;
use App\Http\Controllers\Api\Procurement\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\DeliveryController;
use App\Http\Controllers\Api\Inventory\InventoryItemController;
use App\Http\Controllers\Api\Inventory\StockCardController;
use App\Http\Controllers\Api\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Api\Inventory\PhysicalCountController;
use App\Http\Controllers\Api\Financial\CashAdvanceController;
use App\Http\Controllers\Api\Financial\DisbursementController;
use App\Http\Controllers\Api\Financial\LiquidationController;

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

// Protected routes (require authentication only)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [LogoutController::class, 'logout']);

    // HR Management - Employees
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/search', [EmployeeController::class, 'search']);
        Route::get('/statistics', [EmployeeController::class, 'statistics']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/{id}/promote', [EmployeeController::class, 'promote']);
    });

    // HR Management - Leave Requests
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/', [LeaveRequestController::class, 'store']);
        Route::get('/pending', [LeaveRequestController::class, 'pending']);
        Route::get('/statistics', [LeaveRequestController::class, 'statistics']);
        Route::get('/{id}', [LeaveRequestController::class, 'show']);
        Route::put('/{id}/recommend', [LeaveRequestController::class, 'recommend']);
        Route::put('/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::put('/{id}/disapprove', [LeaveRequestController::class, 'disapprove']);
        Route::put('/{id}/cancel', [LeaveRequestController::class, 'cancel']);
    });

    // HR Management - Service Records
    Route::prefix('service-records')->group(function () {
        Route::get('/employee/{employeeId}', [ServiceRecordController::class, 'byEmployee']);
        Route::post('/', [ServiceRecordController::class, 'store']);
        Route::get('/{id}', [ServiceRecordController::class, 'show']);
        Route::put('/{id}', [ServiceRecordController::class, 'update']);
        Route::delete('/{id}', [ServiceRecordController::class, 'destroy']);
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
    });

    // Inventory Management - Inventory Items
    Route::prefix('inventory-items')->group(function () {
        Route::get('/', [InventoryItemController::class, 'index']);
        Route::post('/', [InventoryItemController::class, 'store']);
        Route::get('/search', [InventoryItemController::class, 'search']);
        Route::get('/with-balances', [InventoryItemController::class, 'withBalances']);
        Route::get('/low-stock', [InventoryItemController::class, 'lowStock']);
        Route::get('/statistics', [InventoryItemController::class, 'statistics']);
        Route::get('/{id}', [InventoryItemController::class, 'show']);
        Route::get('/{id}/with-balance', [InventoryItemController::class, 'showWithBalance']);
        Route::put('/{id}', [InventoryItemController::class, 'update']);
        Route::delete('/{id}', [InventoryItemController::class, 'destroy']);
    });

    // Inventory Management - Stock Cards
    Route::prefix('stock-cards')->group(function () {
        Route::get('/', [StockCardController::class, 'index']);
        Route::get('/{id}', [StockCardController::class, 'show']);
        Route::post('/stock-in', [StockCardController::class, 'stockIn']);
        Route::post('/stock-out', [StockCardController::class, 'stockOut']);
        Route::post('/donation', [StockCardController::class, 'recordDonation']);
        Route::get('/item/{itemId}', [StockCardController::class, 'byInventoryItem']);
        Route::get('/item/{itemId}/balance', [StockCardController::class, 'currentBalance']);
    });

    // Inventory Management - Inventory Adjustments
    Route::prefix('inventory-adjustments')->group(function () {
        Route::get('/', [InventoryAdjustmentController::class, 'index']);
        Route::post('/', [InventoryAdjustmentController::class, 'store']);
        Route::get('/pending', [InventoryAdjustmentController::class, 'pending']);
        Route::get('/{id}', [InventoryAdjustmentController::class, 'show']);
        Route::put('/{id}', [InventoryAdjustmentController::class, 'update']);
        Route::delete('/{id}', [InventoryAdjustmentController::class, 'destroy']);
        Route::put('/{id}/approve', [InventoryAdjustmentController::class, 'approve']);
        Route::put('/{id}/reject', [InventoryAdjustmentController::class, 'reject']);
    });

    // Inventory Management - Physical Counts
    Route::prefix('physical-counts')->group(function () {
        Route::get('/', [PhysicalCountController::class, 'index']);
        Route::post('/', [PhysicalCountController::class, 'store']);
        Route::get('/with-variances', [PhysicalCountController::class, 'withVariances']);
        Route::get('/{id}', [PhysicalCountController::class, 'show']);
        Route::put('/{id}', [PhysicalCountController::class, 'update']);
        Route::delete('/{id}', [PhysicalCountController::class, 'destroy']);
    });

    // Financial Management - Cash Advances
    Route::prefix('cash-advances')->group(function () {
        Route::get('/', [CashAdvanceController::class, 'index']);
        Route::post('/', [CashAdvanceController::class, 'store']);
        Route::get('/pending', [CashAdvanceController::class, 'pending']);
        Route::get('/overdue', [CashAdvanceController::class, 'overdue']);
        Route::get('/statistics', [CashAdvanceController::class, 'statistics']);
        Route::get('/employee/{employeeId}', [CashAdvanceController::class, 'byEmployee']);
        Route::get('/{id}', [CashAdvanceController::class, 'show']);
        Route::put('/{id}', [CashAdvanceController::class, 'update']);
        Route::delete('/{id}', [CashAdvanceController::class, 'destroy']);
        Route::put('/{id}/approve', [CashAdvanceController::class, 'approve']);
        Route::put('/{id}/release', [CashAdvanceController::class, 'release']);
    });

    // Financial Management - Disbursements
    Route::prefix('disbursements')->group(function () {
        Route::get('/', [DisbursementController::class, 'index']);
        Route::post('/', [DisbursementController::class, 'store']);
        Route::get('/pending', [DisbursementController::class, 'pending']);
        Route::get('/statistics', [DisbursementController::class, 'statistics']);
        Route::get('/{id}', [DisbursementController::class, 'show']);
        Route::put('/{id}', [DisbursementController::class, 'update']);
        Route::delete('/{id}', [DisbursementController::class, 'destroy']);
        Route::put('/{id}/certify', [DisbursementController::class, 'certify']);
        Route::put('/{id}/approve', [DisbursementController::class, 'approve']);
        Route::put('/{id}/mark-paid', [DisbursementController::class, 'markPaid']);
    });

    // Financial Management - Liquidations
    Route::prefix('liquidations')->group(function () {
        Route::get('/', [LiquidationController::class, 'index']);
        Route::post('/', [LiquidationController::class, 'store']);
        Route::get('/pending', [LiquidationController::class, 'pending']);
        Route::get('/cash-advance/{caId}', [LiquidationController::class, 'byCashAdvance']);
        Route::get('/{id}', [LiquidationController::class, 'show']);
        Route::put('/{id}', [LiquidationController::class, 'update']);
        Route::delete('/{id}', [LiquidationController::class, 'destroy']);
        Route::post('/{id}/items', [LiquidationController::class, 'addItem']);
        Route::put('/{id}/approve', [LiquidationController::class, 'approve']);
        Route::put('/{id}/reject', [LiquidationController::class, 'reject']);
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
