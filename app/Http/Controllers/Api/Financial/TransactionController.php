<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Get all transactions with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Transaction::with([
                'budget',
                'expense',
                'employee',
                'verifiedBy'
            ]);

            // Filters
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('fund_source')) {
                $query->where('fund_source', $request->fund_source);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('transaction_number', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('reference_number', 'like', '%' . $request->search . '%');
                });
            }

            $perPage = $request->input('per_page', 15);
            $transactions = $query->latest('transaction_date')->paginate($perPage);

            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transaction by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transaction = Transaction::with([
                'budget',
                'expense',
                'disbursement',
                'cashAdvance',
                'purchaseOrder',
                'employee',
                'verifiedBy'
            ])->findOrFail($id);

            return response()->json(['data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new transaction
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transaction_date' => 'required|date',
                'type' => 'required|in:Income,Expense,Transfer,Adjustment',
                'category' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string',
                'fund_source' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'reference_number' => 'nullable|string',
                'payer' => 'nullable|string',
                'payee' => 'nullable|string',
                'budget_id' => 'nullable|exists:budgets,id',
                'employee_id' => 'nullable|exists:employees,id',
            ]);

            // Generate transaction number
            $validated['transaction_number'] = $this->generateTransactionNumber();
            $validated['status'] = $validated['status'] ?? 'Completed';

            $transaction = Transaction::create($validated);

            return response()->json([
                'message' => 'Transaction created successfully.',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update transaction
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->update($request->all());

            return response()->json([
                'message' => 'Transaction updated successfully.',
                'data' => $transaction->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete transaction
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->delete();

            return response()->json(['message' => 'Transaction deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $query = Transaction::query();

            if ($dateFrom && $dateTo) {
                $query->dateRange($dateFrom, $dateTo);
            }

            $stats = [
                'total_transactions' => $query->count(),
                'total_income' => (float) $query->clone()->income()->sum('amount'),
                'total_expenses' => (float) $query->clone()->expense()->sum('amount'),
                'by_type' => $query->clone()->select('type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                    ->groupBy('type')
                    ->get(),
                'by_category' => $query->clone()->select('category', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                    ->groupBy('category')
                    ->get(),
                'by_fund_source' => $query->clone()->select('fund_source', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                    ->whereNotNull('fund_source')
                    ->groupBy('fund_source')
                    ->get(),
                'by_status' => $query->clone()->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
            ];

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent transactions
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);

            $transactions = Transaction::with(['budget', 'employee'])
                ->latest('transaction_date')
                ->limit($limit)
                ->get();

            return response()->json(['data' => $transactions]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify transaction
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);

            $transaction->update([
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'status' => 'Completed',
            ]);

            return response()->json([
                'message' => 'Transaction verified successfully.',
                'data' => $transaction->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate unique transaction number
     */
    private function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $lastTransaction = Transaction::whereDate('created_at', today())
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "TXN-{$date}-{$newNumber}";
    }
}
