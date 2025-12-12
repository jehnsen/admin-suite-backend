<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    protected $trainingService;

    public function __construct(TrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    /**
     * Get all trainings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'employee_id',
                'training_type',
                'status',
                'year',
                'date_from',
                'date_to',
                'venue_type',
                'search'
            ]);
            $perPage = $request->input('per_page', 15);

            $trainings = $this->trainingService->getAllTrainings($filters, $perPage);

            return response()->json($trainings);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get training by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $training = $this->trainingService->getTrainingById($id);

            if (!$training) {
                return response()->json(['message' => 'Training not found.'], 404);
            }

            return response()->json(['data' => $training]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainings by employee ID
     */
    public function byEmployee(int $employeeId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $trainings = $this->trainingService->getTrainingsByEmployee($employeeId, $perPage);

            return response()->json($trainings);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get completed trainings for an employee
     */
    public function completedByEmployee(int $employeeId): JsonResponse
    {
        try {
            $trainings = $this->trainingService->getCompletedTrainings($employeeId);

            return response()->json(['data' => $trainings]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new training record
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $training = $this->trainingService->createTraining($request->all());

            return response()->json([
                'message' => 'Training record created successfully.',
                'data' => $training,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update training record
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $training = $this->trainingService->updateTraining($id, $request->all());

            return response()->json([
                'message' => 'Training record updated successfully.',
                'data' => $training,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete training record
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->trainingService->deleteTraining($id);

            if (!$deleted) {
                return response()->json(['message' => 'Training not found.'], 404);
            }

            return response()->json(['message' => 'Training record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainings by type
     */
    public function byType(string $type, Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $trainings = $this->trainingService->getTrainingsByType($type, $perPage);

            return response()->json($trainings);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainings by year
     */
    public function byYear(int $year, Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $trainings = $this->trainingService->getTrainingsByYear($year, $perPage);

            return response()->json($trainings);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get training statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $employeeId = $request->input('employee_id');
            $stats = $this->trainingService->getTrainingStatistics($employeeId);

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
