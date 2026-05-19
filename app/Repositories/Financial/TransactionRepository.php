<?php

namespace App\Repositories\Financial;

use App\Interfaces\Financial\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function getAllTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::with(['budget', 'expense', 'employee', 'verifiedBy']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        return $query->latest('transaction_date')->paginate($perPage);
    }

    public function getTransactionById(int $id): ?Transaction
    {
        return Transaction::with([
            'budget',
            'expense',
            'disbursement',
            'cashAdvance',
            'purchaseOrder',
            'employee',
            'verifiedBy',
        ])->find($id);
    }

    public function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function updateTransaction(int $id, array $data): Transaction
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function deleteTransaction(int $id): bool
    {
        $transaction = Transaction::findOrFail($id);
        return $transaction->delete();
    }

    public function verifyTransaction(int $id, int $verifiedBy): Transaction
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update([
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'status'      => 'Completed',
        ]);
        return $transaction->fresh();
    }

    public function getTransactionStatistics(array $filters = []): array
    {
        $query = Transaction::query();

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->dateRange($filters['date_from'], $filters['date_to']);
        }

        return [
            'total_transactions' => $query->count(),
            'total_income'       => (float) $query->clone()->income()->sum('amount'),
            'total_expenses'     => (float) $query->clone()->expense()->sum('amount'),
            'by_type'            => $query->clone()
                ->select('type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('type')
                ->get(),
            'by_category'        => $query->clone()
                ->select('category', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('category')
                ->get(),
            'by_fund_source'     => $query->clone()
                ->select('fund_source', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->whereNotNull('fund_source')
                ->groupBy('fund_source')
                ->get(),
            'by_status'          => $query->clone()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
        ];
    }

    public function getRecentTransactions(int $limit = 10): Collection
    {
        return Transaction::with(['budget', 'employee'])
            ->latest('transaction_date')
            ->limit($limit)
            ->get();
    }
}
