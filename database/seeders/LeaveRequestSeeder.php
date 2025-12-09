<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Carbon\Carbon;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $schoolHead = Employee::where('position', 'Principal IV')->first();
        $adminOfficer = Employee::where('position', 'Administrative Officer IV')->first();

        // Create sample leave requests
        $leaveRequests = [
            [
                'employee_id' => $employees->random()->id,
                'leave_type' => 'Vacation Leave',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(12),
                'days_requested' => 3.00,
                'reason' => 'Family bonding and personal matters',
                'status' => 'Pending',
            ],
            [
                'employee_id' => $employees->random()->id,
                'leave_type' => 'Sick Leave',
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->subDays(3),
                'days_requested' => 3.00,
                'sick_leave_type' => 'Out Patient',
                'illness' => 'Flu and fever',
                'status' => 'Approved',
                'recommended_by' => $adminOfficer->id,
                'recommended_at' => Carbon::now()->subDays(4),
                'recommendation_remarks' => 'Recommended for approval',
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subDays(3),
                'approval_remarks' => 'Approved',
            ],
            [
                'employee_id' => $employees->random()->id,
                'leave_type' => 'Special Privilege Leave',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(17),
                'days_requested' => 3.00,
                'reason' => 'Attending family wedding in the province',
                'status' => 'Recommended',
                'recommended_by' => $adminOfficer->id,
                'recommended_at' => Carbon::now()->subDay(),
                'recommendation_remarks' => 'Employee has good attendance record',
            ],
            [
                'employee_id' => $employees->random()->id,
                'leave_type' => 'Maternity Leave',
                'start_date' => Carbon::now()->addMonth(),
                'end_date' => Carbon::now()->addMonth()->addDays(60),
                'days_requested' => 60.00,
                'reason' => 'Maternity leave for childbirth',
                'status' => 'Approved',
                'recommended_by' => $adminOfficer->id,
                'recommended_at' => Carbon::now()->subDays(2),
                'approved_by' => $schoolHead->id,
                'approved_at' => Carbon::now()->subDay(),
                'approval_remarks' => 'Approved as per CSC guidelines',
            ],
            [
                'employee_id' => $employees->random()->id,
                'leave_type' => 'Vacation Leave',
                'start_date' => Carbon::now()->subMonth(),
                'end_date' => Carbon::now()->subMonth()->addDays(5),
                'days_requested' => 5.00,
                'reason' => 'Provincial vacation with family',
                'status' => 'Disapproved',
                'disapproved_by' => $schoolHead->id,
                'disapproved_at' => Carbon::now()->subMonth()->addDay(),
                'disapproval_reason' => 'Insufficient manpower during examination period',
            ],
        ];

        foreach ($leaveRequests as $leaveRequestData) {
            LeaveRequest::create($leaveRequestData);
        }

        $this->command->info('Leave requests created successfully!');
    }
}
