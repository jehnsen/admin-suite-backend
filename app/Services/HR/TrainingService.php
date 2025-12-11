<?php

namespace App\Services\HR;

use App\Interfaces\HR\TrainingRepositoryInterface;
use App\Models\Training;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TrainingService
{
    protected $trainingRepository;

    public function __construct(TrainingRepositoryInterface $trainingRepository)
    {
        $this->trainingRepository = $trainingRepository;
    }

    public function getAllTrainings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->trainingRepository->getAllTrainings($filters, $perPage);
    }

    public function getTrainingById(int $id): ?Training
    {
        return $this->trainingRepository->getTrainingById($id);
    }

    public function getTrainingsByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->trainingRepository->getTrainingsByEmployee($employeeId, $perPage);
    }

    public function createTraining(array $data): Training
    {
        // Add created_by field if authenticated user exists
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return $this->trainingRepository->createTraining($data);
    }

    public function updateTraining(int $id, array $data): Training
    {
        // Add updated_by field if authenticated user exists
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        return $this->trainingRepository->updateTraining($id, $data);
    }

    public function deleteTraining(int $id): bool
    {
        return $this->trainingRepository->deleteTraining($id);
    }

    public function getTrainingsByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->trainingRepository->getTrainingsByType($type, $perPage);
    }

    public function getTrainingsByYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->trainingRepository->getTrainingsByYear($year, $perPage);
    }

    public function getCompletedTrainings(int $employeeId): Collection
    {
        return $this->trainingRepository->getCompletedTrainings($employeeId);
    }

    public function getTrainingStatistics(int $employeeId = null): array
    {
        return $this->trainingRepository->getTrainingStatistics($employeeId);
    }
}
