<?php

namespace App\Interfaces\Financial;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function getAllTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getTransactionById(int $id): ?Transaction;
    public function createTransaction(array $data): Transaction;
    public function updateTransaction(int $id, array $data): Transaction;
    public function deleteTransaction(int $id): bool;
    public function verifyTransaction(int $id, int $verifiedBy): Transaction;
    public function getTransactionStatistics(array $filters = []): array;
    public function getRecentTransactions(int $limit = 10): Collection;
}
