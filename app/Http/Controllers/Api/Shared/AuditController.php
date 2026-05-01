<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Services\Shared\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get audit logs with filters
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'module' => 'nullable|string|in:HR,Procurement,Financial,Inventory,System',
            'event' => 'nullable|string|in:created,updated,deleted',
            'causer_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = $request->only(['module', 'event', 'causer_id', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 50);

        $logs = $this->auditService->getAuditLogs($filters, $perPage);

        return response()->json($logs);
    }

    /**
     * Get audit history for a specific entity
     */
    public function entityHistory(Request $request): JsonResponse
    {
        $allowed = [
            'Employee', 'User', 'LeaveRequest', 'ServiceRecord', 'Training',
            'AttendanceRecord', 'ServiceCredit', 'Supplier', 'PurchaseRequest',
            'Quotation', 'PurchaseOrder', 'Delivery', 'InventoryItem', 'StockCard',
            'InventoryAdjustment', 'PhysicalCount', 'Budget', 'CashAdvance',
            'Disbursement', 'Liquidation', 'Transaction', 'Document',
        ];

        $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', $allowed)],
            'id' => 'required|integer',
        ]);

        $type = "App\\Models\\{$request->input('type')}";
        $id = $request->input('id');

        $history = $this->auditService->getEntityHistory($type, $id);

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Get audit report with statistics
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $request->only(['date_from', 'date_to']);
        $report = $this->auditService->generateAuditReport($filters);

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * Get current user's activity log
     */
    public function myActivity(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request, 50);
        $logs = $this->auditService->getUserActivity($request->user()->id, $perPage);

        return response()->json($logs);
    }

    /**
     * Get audit logs for a specific module
     */
    public function byModule(Request $request, string $module): JsonResponse
    {
        $allowedModules = ['HR', 'Procurement', 'Financial', 'Inventory', 'System'];

        if (!in_array($module, $allowedModules, true)) {
            return response()->json(['message' => 'Invalid module specified.'], 422);
        }

        $perPage = $this->getPerPage($request, 50);
        $logs = $this->auditService->getModuleActivity($module, $perPage);

        return response()->json($logs);
    }

    /**
     * Get single audit log details
     */
    public function show(int $id): JsonResponse
    {
        $log = $this->auditService->getAuditLogDetails($id);

        if (!$log) {
            return response()->json([
                'message' => 'Audit log not found.',
            ], 404);
        }

        return response()->json([
            'data' => $log,
        ]);
    }

    /**
     * Export audit logs for COA compliance
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'module' => 'nullable|string|in:HR,Procurement,Financial,Inventory,System',
        ]);

        $filters = $request->only(['date_from', 'date_to', 'module']);
        $export = $this->auditService->exportAuditLogsForCOA($filters);

        return response()->json([
            'data' => $export,
        ]);
    }
}
