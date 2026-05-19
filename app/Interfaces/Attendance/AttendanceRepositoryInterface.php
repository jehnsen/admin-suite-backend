<?php

namespace App\Interfaces\Attendance;

use App\Models\AttendanceImportBatch;
use App\Models\AttendanceLog;
use App\Models\DailyTimeRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AttendanceRepositoryInterface
{
    // --- Import Batches ---

    public function createBatch(array $data): AttendanceImportBatch;

    public function updateBatch(int $id, array $data): AttendanceImportBatch;

    public function findBatchById(int $id): ?AttendanceImportBatch;

    public function getAllBatches(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    // --- Raw Logs ---

    public function createLog(array $data): AttendanceLog;

    public function bulkCreateLogs(array $logs): int;

    public function getLogsByBatch(int $batchId): Collection;

    public function getLogsByEmployee(int $employeeId, string $start, string $end): Collection;

    // --- Daily Time Records ---

    public function upsertDtr(int $employeeId, string $logDate, array $data): DailyTimeRecord;

    public function findDtr(int $employeeId, string $logDate): ?DailyTimeRecord;

    public function getDtrByEmployee(int $employeeId, array $filters = [], int $perPage = 31): LengthAwarePaginator;

    public function getDtrSummary(int $employeeId, int $year, int $month): array;

    public function getDtrByBatch(int $batchId): Collection;

    public function getAllDtr(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
