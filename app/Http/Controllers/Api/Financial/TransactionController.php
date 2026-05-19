<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\TransactionResource;
use App\Http\Requests\Financial\StoreTransactionRequest;
use App\Http\Requests\Financial\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\Financial\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['type', 'category', 'fund_source', 'status', 'date_from', 'date_to', 'search']);
            $transactions = $this->transactionService->getAllTransactions($filters, $this->getPerPage($request));

            return TransactionResource::collection($transactions);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = Transaction::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $transaction = $this->transactionService->getTransactionById($id);

            if (!$transaction) {
                return response()->json(['message' => 'Record not found.'], 404);
            }

            return response()->json(['data' => new TransactionResource($transaction)]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createTransaction($request->validated());

            return response()->json([
                'message' => 'Transaction created successfully.',
                'data'    => new TransactionResource($transaction),
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateTransactionRequest $request, string $uuid): JsonResponse
    {
        $id = Transaction::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $transaction = $this->transactionService->updateTransaction($id, $request->validated());

            return response()->json([
                'message' => 'Transaction updated successfully.',
                'data'    => new TransactionResource($transaction),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $id = Transaction::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->transactionService->deleteTransaction($id);

            return response()->json(['message' => 'Transaction deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to']);
            $stats = $this->transactionService->getTransactionStatistics($filters);

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $transactions = $this->transactionService->getRecentTransactions($limit);

            return response()->json(['data' => TransactionResource::collection($transactions)]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function verify(Request $request, string $uuid): JsonResponse
    {
        $id = Transaction::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $transaction = $this->transactionService->verifyTransaction($id, $request->user()->id);

            return response()->json([
                'message' => 'Transaction verified successfully.',
                'data'    => new TransactionResource($transaction),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
