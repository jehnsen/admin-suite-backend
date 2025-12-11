<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\TrainingRepositoryInterface;
use App\Models\Training;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingRepository implements TrainingRepositoryInterface
{
    public function getAllTrainings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Training::with(['employee', 'createdBy', 'updatedBy']);

        // Filter by employee
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        // Filter by training type
        if (!empty($filters['training_type'])) {
            $query->where('training_type', $filters['training_type']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by year
        if (!empty($filters['year'])) {
            $query->whereYear('date_from', $filters['year']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('date_from', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('date_to', '<=', $filters['date_to']);
        }

        // Filter by venue type
        if (!empty($filters['venue_type'])) {
            $query->where('venue_type', $filters['venue_type']);
        }

        // Search by training title
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('training_title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('conducted_by', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('date_from', 'desc')->paginate($perPage);
    }

    public function getTrainingById(int $id): ?Training
    {
        return Training::with(['employee', 'createdBy', 'updatedBy'])->find($id);
    }

    public function getTrainingsByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return Training::with(['createdBy', 'updatedBy'])
            ->where('employee_id', $employeeId)
            ->orderBy('date_from', 'desc')
            ->paginate($perPage);
    }

    public function createTraining(array $data): Training
    {
        return Training::create($data);
    }

    public function updateTraining(int $id, array $data): Training
    {
        $training = Training::findOrFail($id);
        $training->update($data);
        return $training->fresh(['employee', 'createdBy', 'updatedBy']);
    }

    public function deleteTraining(int $id): bool
    {
        $training = Training::findOrFail($id);
        return $training->delete();
    }

    public function getTrainingsByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return Training::with(['employee', 'createdBy'])
            ->where('training_type', $type)
            ->orderBy('date_from', 'desc')
            ->paginate($perPage);
    }

    public function getTrainingsByYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return Training::with(['employee', 'createdBy'])
            ->whereYear('date_from', $year)
            ->orderBy('date_from', 'desc')
            ->paginate($perPage);
    }

    public function getCompletedTrainings(int $employeeId): Collection
    {
        return Training::where('employee_id', $employeeId)
            ->where('status', 'Completed')
            ->orderBy('date_from', 'desc')
            ->get();
    }

    public function getTrainingStatistics(int $employeeId = null): array
    {
        $query = Training::query();

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $totalTrainings = (clone $query)->count();
        $completedTrainings = (clone $query)->where('status', 'Completed')->count();
        $ongoingTrainings = (clone $query)->where('status', 'Ongoing')->count();
        $totalHours = (clone $query)->sum('number_of_hours');
        $totalLdUnits = (clone $query)->sum('ld_units');

        // Training type breakdown
        $typeBreakdown = (clone $query)
            ->select('training_type', DB::raw('COUNT(*) as count'))
            ->groupBy('training_type')
            ->get()
            ->pluck('count', 'training_type')
            ->toArray();

        // Yearly breakdown
        $yearlyBreakdown = (clone $query)
            ->select(DB::raw('YEAR(date_from) as year'), DB::raw('COUNT(*) as count'))
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get()
            ->pluck('count', 'year')
            ->toArray();

        return [
            'total_trainings' => $totalTrainings,
            'completed_trainings' => $completedTrainings,
            'ongoing_trainings' => $ongoingTrainings,
            'total_hours' => $totalHours,
            'total_ld_units' => $totalLdUnits,
            'type_breakdown' => $typeBreakdown,
            'yearly_breakdown' => $yearlyBreakdown,
        ];
    }
}
