<?php

namespace App\Repositories\Financial;

use App\Interfaces\Financial\DisbursementRepositoryInterface;
use App\Models\Disbursement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DisbursementRepository implements DisbursementRepositoryInterface
{
    public function getAllDisbursements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Disbursement::with(['purchaseOrder', 'expense', 'cashAdvance', 'budget']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['payment_mode'])) {
            $query->where('payment_mode', $filters['payment_mode']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('dv_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('dv_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('dv_date', 'desc')->paginate($perPage);
    }

    public function getDisbursementById(int $id): ?Disbursement
    {
        return Disbursement::with([
            'purchaseOrder',
            'expense',
            'cashAdvance',
            'budget',
            'certifiedBy',
            'approvedBy',
            'paidBy'
        ])->find($id);
    }

    public function createDisbursement(array $data): Disbursement
    {
        return Disbursement::create($data);
    }

    public function updateDisbursement(int $id, array $data): Disbursement
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update($data);
        return $disbursement->fresh();
    }

    public function deleteDisbursement(int $id): bool
    {
        $disbursement = Disbursement::findOrFail($id);
        return $disbursement->delete();
    }

    public function certifyDisbursement(int $id, int $certifiedBy): Disbursement
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update([
            'status' => 'Certified',
            'certified_by' => $certifiedBy,
            'certified_at' => now(),
        ]);
        return $disbursement->fresh();
    }

    public function approveDisbursement(int $id, int $approvedBy): Disbursement
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update([
            'status' => 'Approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $disbursement->fresh();
    }

    public function markAsPaid(int $id, int $paidBy): Disbursement
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->update([
            'status' => 'Paid',
            'paid_by' => $paidBy,
            'paid_at' => now(),
        ]);
        return $disbursement->fresh();
    }

    public function getPendingDisbursements(int $perPage = 15): LengthAwarePaginator
    {
        return Disbursement::where('status', 'Pending')
            ->orderBy('dv_date', 'desc')
            ->paginate($perPage);
    }

    public function getDisbursementStatistics(): array
    {
        return [
            'total_disbursements' => Disbursement::count(),
            'pending' => Disbursement::where('status', 'Pending')->count(),
            'certified' => Disbursement::where('status', 'Certified')->count(),
            'approved' => Disbursement::where('status', 'Approved')->count(),
            'paid' => Disbursement::where('status', 'Paid')->count(),
            'total_amount_disbursed' => Disbursement::where('status', 'Paid')->sum('net_amount'),
            'by_fund_source' => Disbursement::select('fund_source', DB::raw('count(*) as count, sum(amount) as total'))
                ->groupBy('fund_source')
                ->get()
                ->keyBy('fund_source'),
        ];
    }
}
