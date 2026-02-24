<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Approved</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .header { background-color: #1a56a5; padding: 28px 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; letter-spacing: 0.5px; }
        .header p { color: #c8ddf5; margin: 4px 0 0; font-size: 13px; }
        .badge { display: inline-block; background-color: #22c55e; color: #ffffff; font-size: 13px; font-weight: bold; padding: 5px 14px; border-radius: 20px; margin: 20px 0 4px; }
        .body { padding: 28px 32px; }
        .greeting { font-size: 15px; color: #374151; margin-bottom: 16px; }
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 18px 22px; margin: 20px 0; }
        .detail-box table { width: 100%; border-collapse: collapse; }
        .detail-box td { padding: 7px 0; font-size: 14px; color: #374151; vertical-align: top; }
        .detail-box td:first-child { font-weight: 600; width: 45%; color: #6b7280; }
        .remarks-box { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px 16px; margin-top: 20px; border-radius: 0 4px 4px 0; font-size: 14px; color: #374151; }
        .footer { background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 18px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer strong { color: #6b7280; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>AdminSuite — Leave Management</h1>
        <p>Department of Education</p>
    </div>

    <div class="body">
        <div style="text-align:center;">
            <div class="badge">✓ Approved</div>
        </div>

        <p class="greeting">
            Dear <strong>{{ $leaveRequest->employee->first_name }} {{ $leaveRequest->employee->last_name }}</strong>,
        </p>

        <p style="font-size:14px; color:#374151;">
            Your leave request has been <strong>approved</strong>. Please see the details below.
        </p>

        <div class="detail-box">
            <table>
                <tr>
                    <td>Leave Type</td>
                    <td>{{ $leaveRequest->leave_type }}</td>
                </tr>
                <tr>
                    <td>Period</td>
                    <td>
                        {{ \Carbon\Carbon::parse($leaveRequest->start_date)->format('M d, Y') }}
                        –
                        {{ \Carbon\Carbon::parse($leaveRequest->end_date)->format('M d, Y') }}
                    </td>
                </tr>
                <tr>
                    <td>Days Requested</td>
                    <td>{{ number_format($leaveRequest->days_requested, 1) }} day(s)</td>
                </tr>
                <tr>
                    <td>Approved On</td>
                    <td>{{ $leaveRequest->approved_at ? \Carbon\Carbon::parse($leaveRequest->approved_at)->format('M d, Y h:i A') : now()->format('M d, Y') }}</td>
                </tr>
                @if($leaveRequest->approver)
                <tr>
                    <td>Approved By</td>
                    <td>{{ $leaveRequest->approver->first_name }} {{ $leaveRequest->approver->last_name }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if($leaveRequest->approval_remarks)
        <div class="remarks-box">
            <strong>Remarks:</strong> {{ $leaveRequest->approval_remarks }}
        </div>
        @endif

        <p style="font-size:13px; color:#6b7280; margin-top:24px;">
            Please ensure you have coordinated with your immediate supervisor before your leave commences.
            This is a system-generated notification — do not reply to this email.
        </p>
    </div>

    <div class="footer">
        <strong>AdminSuite</strong> — DepEd Administrative Management System<br>
        This email was sent automatically. Please do not reply.
    </div>
</div>
</body>
</html>
