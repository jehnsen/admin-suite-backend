<?php

namespace App\Services\Financial;

use App\Interfaces\Financial\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionService
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    public function getAllTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getAllTransactions($filters, $perPage);
    }

    public function getTransactionById(int $id): ?Transaction
    {
        return $this->transactionRepository->getTransactionById($id);
    }

    public function createTransaction(array $data): Transaction
    {
        $data['transaction_number'] = $this->generateTransactionNumber();
        $data['status'] = 'Completed';

        return $this->transactionRepository->createTransaction($data);
    }

    public function updateTransaction(int $id, array $data): Transaction
    {
        return $this->transactionRepository->updateTransaction($id, $data);
    }

    public function deleteTransaction(int $id): bool
    {
        return $this->transactionRepository->deleteTransaction($id);
    }

    public function verifyTransaction(int $id, int $verifiedBy): Transaction
    {
        return $this->transactionRepository->verifyTransaction($id, $verifiedBy);
    }

    public function getTransactionStatistics(array $filters = []): array
    {
        return $this->transactionRepository->getTransactionStatistics($filters);
    }

    public function getRecentTransactions(int $limit = 10): Collection
    {
        return $this->transactionRepository->getRecentTransactions($limit);
    }

    private function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $lastTransaction = Transaction::withTrashed()
            ->whereDate('created_at', today())
            ->orderBy('transaction_number', 'desc')
            ->first();

        $newNumber = $lastTransaction
            ? str_pad((int) substr($lastTransaction->transaction_number, -4) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        return "TXN-{$date}-{$newNumber}";
    }
}
