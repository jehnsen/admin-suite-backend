<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\ReportService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function form6(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->reportService->getForm6Data($id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch report data.'], 500);
        }
    }

    public function ris(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->reportService->getRisData($id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Requisition slip not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch report data.'], 500);
        }
    }

    public function dv(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->reportService->getDvData($id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Disbursement not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch report data.'], 500);
        }
    }

    public function iar(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->reportService->getIarData($id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Delivery not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch report data.'], 500);
        }
    }

    public function pds(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->reportService->getPdsData($id)]);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Employee not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch report data.'], 500);
        }
    }
}
