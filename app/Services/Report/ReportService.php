<?php

namespace App\Services\Report;

use App\Models\Delivery;
use App\Models\Disbursement;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\RequisitionSlip;

class ReportService
{
    public function getForm6Data(string $uuid): array
    {
        $id = LeaveRequest::where('uuid', $uuid)->value('id') ?? 0;
        $leave = LeaveRequest::with(['employee', 'recommender', 'approver'])
            ->findOrFail($id);

        $employee = $leave->employee;

        return [
            'leave_request' => [
                'id'                     => $leave->uuid,
                'leave_type'             => $leave->leave_type,
                'sick_leave_type'        => $leave->sick_leave_type,
                'illness'                => $leave->illness,
                'reason'                 => $leave->reason,
                'start_date'             => $leave->start_date?->format('Y-m-d'),
                'end_date'               => $leave->end_date?->format('Y-m-d'),
                'days_requested'         => (float) ($leave->days_requested ?? 0),
                'status'                 => $leave->status,
                'recommendation_remarks' => $leave->recommendation_remarks,
                'approval_remarks'       => $leave->approval_remarks,
                'disapproval_reason'     => $leave->disapproval_reason,
                'recommended_at'         => $leave->recommended_at?->format('Y-m-d H:i:s'),
                'approved_at'            => $leave->approved_at?->format('Y-m-d H:i:s'),
                'disapproved_at'         => $leave->disapproved_at?->format('Y-m-d H:i:s'),
            ],
            'employee' => $employee ? [
                'id'                    => $employee->uuid,
                'employee_number'       => $employee->employee_number,
                'full_name'             => $employee->full_name,
                'first_name'            => $employee->first_name,
                'middle_name'           => $employee->middle_name,
                'last_name'             => $employee->last_name,
                'position'              => $employee->position,
                'position_title'        => $employee->position_title,
                'employment_status'     => $employee->employment_status,
                'salary_grade'          => $employee->salary_grade,
                'step_increment'        => $employee->step_increment,
                'date_hired'            => $employee->date_hired?->format('Y-m-d'),
                'vacation_leave_credits' => (float) ($employee->vacation_leave_credits ?? 0),
                'sick_leave_credits'    => (float) ($employee->sick_leave_credits ?? 0),
            ] : null,
            'recommender' => $leave->recommender ? [
                'id'       => $leave->recommender->uuid,
                'full_name' => $leave->recommender->full_name,
                'position' => $leave->recommender->position,
            ] : null,
            'approver' => $leave->approver ? [
                'id'       => $leave->approver->uuid,
                'full_name' => $leave->approver->full_name,
                'position' => $leave->approver->position,
            ] : null,
        ];
    }

    public function getRisData(string $uuid): array
    {
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        $slip = RequisitionSlip::with([
            'requestedByEmployee',
            'approvedByEmployee',
            'releasedByEmployee',
            'items.inventoryItem',
        ])->findOrFail($id);

        return [
            'slip' => [
                'id'         => $slip->uuid,
                'ris_number' => $slip->ris_number,
                'date'       => $slip->requested_date?->format('Y-m-d') ?? $slip->created_at?->format('Y-m-d'),
                'purpose'    => $slip->purpose,
                'status'     => $slip->status,
                'remarks'    => $slip->remarks ?? null,
            ],
            'requested_by' => $slip->requestedByEmployee ? [
                'id'       => $slip->requestedByEmployee->uuid,
                'full_name' => $slip->requestedByEmployee->full_name,
                'position' => $slip->requestedByEmployee->position,
            ] : null,
            'approved_by' => $slip->approvedByEmployee ? [
                'id'       => $slip->approvedByEmployee->uuid,
                'full_name' => $slip->approvedByEmployee->full_name,
                'position' => $slip->approvedByEmployee->position,
            ] : null,
            'released_by' => $slip->releasedByEmployee ? [
                'id'       => $slip->releasedByEmployee->uuid,
                'full_name' => $slip->releasedByEmployee->full_name,
                'position' => $slip->releasedByEmployee->position,
            ] : null,
            'items' => $slip->items->map(fn($item) => [
                'id'                => $item->uuid,
                'description'       => $item->description,
                'unit'              => $item->unit,
                'quantity_requested' => (int) ($item->quantity_requested ?? 0),
                'quantity_approved'  => (int) ($item->quantity_approved ?? 0),
                'quantity_issued'    => (int) ($item->quantity_issued ?? 0),
                'remarks'           => $item->remarks ?? null,
                'inventory_item'    => $item->inventoryItem ? [
                    'id'        => $item->inventoryItem->uuid,
                    'item_code' => $item->inventoryItem->item_code,
                    'item_name' => $item->inventoryItem->item_name,
                ] : null,
            ])->values()->all(),
        ];
    }

    public function getDvData(string $uuid): array
    {
        $id = Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        $dv = Disbursement::with([
            'purchaseOrder',
            'certifiedBy.employee',
            'approvedBy.employee',
            'paidBy.employee',
        ])->findOrFail($id);

        $userShape = fn($user) => $user ? [
            'id'                => $user->uuid,
            'name'              => $user->name,
            'employee_full_name' => $user->employee?->full_name,
        ] : null;

        return [
            'disbursement' => [
                'id'           => $dv->uuid,
                'dv_number'    => $dv->dv_number,
                'dv_date'      => $dv->dv_date?->format('Y-m-d'),
                'payee_name'   => $dv->payee_name,
                'purpose'      => $dv->purpose,
                'fund_source'  => $dv->fund_source,
                'payment_mode' => $dv->payment_mode,
                'check_number' => $dv->check_number,
                'check_date'   => $dv->check_date?->format('Y-m-d'),
                'amount'       => (float) ($dv->amount ?? 0),
                'gross_amount' => (float) ($dv->gross_amount ?? 0),
                'net_amount'   => (float) ($dv->net_amount ?? 0),
                'status'       => $dv->status,
                'remarks'      => $dv->remarks,
            ],
            'purchase_order' => $dv->purchaseOrder ? [
                'id'        => $dv->purchaseOrder->uuid,
                'po_number' => $dv->purchaseOrder->po_number,
            ] : null,
            'certified_by' => $userShape($dv->certifiedBy),
            'certified_at' => $dv->certified_at?->format('Y-m-d H:i:s'),
            'approved_by'  => $userShape($dv->approvedBy),
            'approved_at'  => $dv->approved_at?->format('Y-m-d H:i:s'),
            'paid_by'      => $userShape($dv->paidBy),
            'paid_at'      => $dv->paid_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function getIarData(string $uuid): array
    {
        $id = Delivery::where('uuid', $uuid)->value('id') ?? 0;
        $delivery = Delivery::with([
            'supplier',
            'purchaseOrder',
            'items.purchaseOrderItem',
            'inspectedBy.employee',
            'receivedBy.employee',
            'acceptedBy.employee',
        ])->findOrFail($id);

        $userShape = fn($user) => $user ? [
            'id'                => $user->uuid,
            'name'              => $user->name,
            'employee_full_name' => $user->employee?->full_name,
        ] : null;

        return [
            'delivery' => [
                'id'                     => $delivery->uuid,
                'delivery_receipt_number' => $delivery->delivery_receipt_number,
                'delivery_date'          => $delivery->delivery_date?->format('Y-m-d'),
                'inspection_result'      => $delivery->inspection_result,
                'inspection_remarks'     => $delivery->inspection_remarks,
                'status'                 => $delivery->status,
                'remarks'                => $delivery->remarks ?? null,
            ],
            'purchase_order' => $delivery->purchaseOrder ? [
                'id'        => $delivery->purchaseOrder->uuid,
                'po_number' => $delivery->purchaseOrder->po_number,
            ] : null,
            'supplier' => $delivery->supplier ? [
                'id'             => $delivery->supplier->uuid,
                'business_name'  => $delivery->supplier->business_name,
                'address'        => $delivery->supplier->address,
                'contact_person' => $delivery->supplier->contact_person,
                'contact_number' => $delivery->supplier->contact_number,
            ] : null,
            'inspected_by' => $userShape($delivery->inspectedBy),
            'inspected_at' => $delivery->inspected_at?->format('Y-m-d H:i:s'),
            'received_by'  => $userShape($delivery->receivedBy),
            'received_at'  => $delivery->received_at?->format('Y-m-d H:i:s'),
            'accepted_by'  => $userShape($delivery->acceptedBy),
            'accepted_at'  => $delivery->accepted_at?->format('Y-m-d H:i:s'),
            'items' => $delivery->items->map(fn($item) => [
                'id'                 => $item->uuid,
                'description'        => $item->item_description ?? $item->description ?? '',
                'unit'               => $item->unit,
                'quantity_delivered' => (int) ($item->quantity_delivered ?? 0),
                'quantity_accepted'  => (int) ($item->quantity_accepted ?? 0),
                'unit_price'         => (float) ($item->purchaseOrderItem?->unit_price ?? 0),
                'total_amount'       => (float) ($item->quantity_accepted ?? 0) * (float) ($item->purchaseOrderItem?->unit_price ?? 0),
                'serial_numbers'     => $item->serial_numbers ?? [],
                'remarks'            => $item->remarks ?? null,
            ])->values()->all(),
        ];
    }

    public function getPdsData(string $uuid): array
    {
        $id = Employee::where('uuid', $uuid)->value('id') ?? 0;
        $employee = Employee::with(['serviceRecords', 'trainings'])
            ->findOrFail($id);

        return [
            'employee' => [
                'id'                  => $employee->uuid,
                'employee_number'     => $employee->employee_number,
                'full_name'           => $employee->full_name,
                'first_name'          => $employee->first_name,
                'middle_name'         => $employee->middle_name,
                'last_name'           => $employee->last_name,
                'suffix'              => $employee->suffix,
                'date_of_birth'       => $employee->date_of_birth?->format('Y-m-d'),
                'gender'              => $employee->gender,
                'civil_status'        => $employee->civil_status,
                'email'               => $employee->email,
                'mobile_number'       => $employee->mobile_number,
                'address'             => $employee->address,
                'city'                => $employee->city,
                'province'            => $employee->province,
                'zip_code'            => $employee->zip_code,
                'plantilla_item_no'   => $employee->plantilla_item_no,
                'position'            => $employee->position,
                'position_title'      => $employee->position_title,
                'salary_grade'        => $employee->salary_grade,
                'step_increment'      => $employee->step_increment,
                'monthly_salary'      => (float) ($employee->monthly_salary ?? 0),
                'employment_status'   => $employee->employment_status,
                'date_hired'          => $employee->date_hired?->format('Y-m-d'),
                'date_separated'      => $employee->date_separated?->format('Y-m-d'),
                'tin'                 => $employee->tin,
                'gsis_number'         => $employee->gsis_number,
                'philhealth_number'   => $employee->philhealth_number,
                'pagibig_number'      => $employee->pagibig_number,
                'sss_number'          => $employee->sss_number,
                'vacation_leave_credits' => (float) ($employee->vacation_leave_credits ?? 0),
                'sick_leave_credits'  => (float) ($employee->sick_leave_credits ?? 0),
                'status'              => $employee->status,
            ],
            'service_records' => $employee->serviceRecords->map(fn($r) => [
                'id'                    => $r->uuid,
                'date_from'             => $r->date_from?->format('Y-m-d'),
                'date_to'               => $r->date_to?->format('Y-m-d'),
                'designation'           => $r->designation,
                'office_entity'         => $r->office_entity,
                'salary'                => (float) ($r->salary ?? 0),
                'status_of_appointment' => $r->status_of_appointment,
                'government_service'    => (bool) $r->government_service,
            ])->values()->all(),
            'trainings' => $employee->trainings->map(fn($t) => [
                'id'              => $t->uuid,
                'training_title'  => $t->training_title,
                'date_from'       => $t->date_from?->format('Y-m-d'),
                'date_to'         => $t->date_to?->format('Y-m-d'),
                'number_of_hours' => (float) ($t->number_of_hours ?? 0),
                'type_of_ld'      => $t->type_of_ld ?? null,
                'conducted_by'    => $t->conducted_by ?? null,
            ])->values()->all(),
        ];
    }
}
