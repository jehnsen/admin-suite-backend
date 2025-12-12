<?php

namespace App\Interfaces\HR;

use App\Models\Training;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TrainingRepositoryInterface
{
    public function getAllTrainings(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getTrainingById(int $id): ?Training;
    public function getTrainingsByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator;
    public function createTraining(array $data): Training;
    public function updateTraining(int $id, array $data): Training;
    public function deleteTraining(int $id): bool;
    public function getTrainingsByType(string $type, int $perPage = 15): LengthAwarePaginator;
    public function getTrainingsByYear(int $year, int $perPage = 15): LengthAwarePaginator;
    public function getCompletedTrainings(int $employeeId): Collection;
    public function getTrainingStatistics(int $employeeId = null): array;
}
