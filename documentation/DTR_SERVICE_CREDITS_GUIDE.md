# DTR & Service Credits Management Guide

## Table of Contents
1. [Overview](#overview)
2. [Phase 1: DTR (Daily Time Record)](#phase-1-dtr-daily-time-record)
3. [Phase 2: Service Credits](#phase-2-service-credits)
4. [API Endpoints](#api-endpoints)
5. [Business Rules](#business-rules)
6. [CSV Import Format](#csv-import-format)
7. [Testing Guide](#testing-guide)

---

## Overview

The DTR and Service Credits system automates attendance tracking and service credit management for Philippine public schools. It addresses the challenge of tracking employee work hours, absences, and compensatory time off for special duties.

### Key Features

**Phase 1 - DTR Foundation:**
- ✅ Automatic calculation of undertime, late minutes, and overtime
- ✅ CSV bulk import for offline-first data entry
- ✅ Approval workflow for attendance records
- ✅ Monthly attendance summaries and statistics
- ✅ Integration with employee profiles

**Phase 2 - Service Credits:**
- ✅ Track service credits earned from summer work, holiday work, overtime, etc.
- ✅ FIFO (First-In-First-Out) credit application algorithm
- ✅ Automatic expiry after 1 year
- ✅ Apply credits to offset absences
- ✅ Revertible offset transactions
- ✅ Approval workflow with remarks

---

## Phase 1: DTR (Daily Time Record)

### Database Schema

#### Employee Fields (Extended)
```sql
service_credit_balance  DECIMAL(5,2) DEFAULT 0.00
standard_time_in        TIME DEFAULT '07:30:00'
standard_time_out       TIME DEFAULT '16:30:00'
daily_rate             DECIMAL(10,2) NULLABLE
```

#### Attendance Records Table
```sql
id                    BIGINT PRIMARY KEY
employee_id           BIGINT FOREIGN KEY
attendance_date       DATE NOT NULL
time_in              TIME NULLABLE
time_out             TIME NULLABLE
lunch_out            TIME NULLABLE
lunch_in             TIME NULLABLE
status               ENUM('Present', 'Absent', 'On Leave', 'Half-Day',
                         'Holiday', 'Weekend', 'Service Credit Used')
undertime_hours      DECIMAL(5,2) DEFAULT 0.00
late_minutes         INTEGER DEFAULT 0
overtime_hours       DECIMAL(5,2) DEFAULT 0.00
import_source        ENUM('Manual Entry', 'CSV Upload',
                         'Biometric Sync', 'Mobile App')
approved_by          BIGINT NULLABLE
approved_at          TIMESTAMP NULLABLE
created_by           BIGINT
remarks              TEXT NULLABLE

UNIQUE INDEX (employee_id, attendance_date)
```

### Automatic Calculations

The system automatically calculates these metrics when creating or updating attendance records:

#### 1. Late Minutes
```php
if (time_in > standard_time_in) {
    late_minutes = time_in - standard_time_in (in minutes)
}
```

**Example:**
- Standard Time In: 07:30:00
- Actual Time In: 08:00:00
- **Result:** `late_minutes = 30`

#### 2. Undertime Hours
```php
if (time_out < standard_time_out) {
    undertime_hours = standard_time_out - time_out (in hours)
}
```

**Example:**
- Standard Time Out: 16:30:00
- Actual Time Out: 15:00:00
- **Result:** `undertime_hours = 1.5`

#### 3. Overtime Hours
```php
if (time_out > standard_time_out) {
    overtime_hours = time_out - standard_time_out (in hours)
}
```

**Example:**
- Standard Time Out: 16:30:00
- Actual Time Out: 18:00:00
- **Result:** `overtime_hours = 1.5`

#### 4. Total Work Hours
```php
total_work_hours = (time_out - time_in) - lunch_break_duration
```

**Example:**
- Time In: 07:30:00
- Lunch Out: 12:00:00
- Lunch In: 13:00:00
- Time Out: 16:30:00
- **Result:** `total_work_hours = 8.0` (9 hours - 1 hour lunch)

### CSV Bulk Import

The system supports CSV import for offline data entry, ideal for schools with poor internet connectivity.

**CSV Format:**
```csv
employee_number,attendance_date,time_in,time_out,lunch_out,lunch_in,status,remarks
EMP-2024-0001,2025-02-01,07:30:00,16:30:00,12:00:00,13:00:00,Present,
EMP-2024-0002,2025-02-01,07:45:00,16:15:00,12:00:00,13:00:00,Present,Late arrival
EMP-2024-0003,2025-02-01,,,,,Absent,Sick leave
```

**Import Logic:**
1. Validates employee_number exists in database
2. Checks for duplicate attendance_date records
3. Calculates undertime/late/overtime automatically
4. Returns success/failure report with error details

**Error Tracking:**
```json
{
  "total": 100,
  "success": 97,
  "failed": 3,
  "errors": [
    {
      "row": 5,
      "employee_number": "EMP-2024-9999",
      "error": "Employee not found"
    },
    {
      "row": 12,
      "employee_number": "EMP-2024-0005",
      "error": "Duplicate attendance record for 2025-02-01"
    }
  ]
}
```

### Approval Workflow

**States:**
- **Unapproved:** Can be edited or deleted
- **Approved:** Locked, cannot be edited or deleted

**Approval Process:**
1. Admin Officer or authorized user creates attendance records
2. School Head reviews and approves
3. Upon approval, record is locked (`approved_by` and `approved_at` set)

**Authorization:**
- **Admin Officer:** Create, edit, approve, delete (unapproved only)
- **School Head:** View all, approve
- **Teacher/Staff:** View own records only

---

## Phase 2: Service Credits

### Database Schema

#### Service Credits Table
```sql
id                  BIGINT PRIMARY KEY
employee_id         BIGINT FOREIGN KEY
credit_type         ENUM('Summer Work', 'Holiday Work', 'Overtime',
                        'Special Duty', 'Weekend Work')
work_date          DATE NOT NULL
description        TEXT NULLABLE
hours_worked       DECIMAL(5,2) NOT NULL
credits_earned     DECIMAL(5,2) NOT NULL (hours_worked / 8)
credits_used       DECIMAL(5,2) DEFAULT 0.00
credits_balance    DECIMAL(5,2) NOT NULL
status             ENUM('Pending', 'Approved', 'Rejected', 'Expired')
expiry_date        DATE NULLABLE (work_date + 1 year)
approved_by        BIGINT NULLABLE
approved_at        TIMESTAMP NULLABLE
rejected_by        BIGINT NULLABLE
rejected_at        TIMESTAMP NULLABLE
rejection_reason   TEXT NULLABLE
created_by         BIGINT

INDEX (employee_id, status)
INDEX (work_date)
INDEX (expiry_date)
```

#### Service Credit Offsets Table
```sql
id                     BIGINT PRIMARY KEY
service_credit_id      BIGINT FOREIGN KEY
attendance_record_id   BIGINT FOREIGN KEY
employee_id           BIGINT FOREIGN KEY
credits_used          DECIMAL(5,2) NOT NULL
offset_date           DATE NOT NULL
reason                TEXT NULLABLE
status                ENUM('Applied', 'Reverted')
applied_by            BIGINT
reverted_at           TIMESTAMP NULLABLE
reverted_by           BIGINT NULLABLE
revert_reason         TEXT NULLABLE

INDEX (service_credit_id)
INDEX (attendance_record_id)
INDEX (employee_id)
```

### Credit Calculation

**Formula:**
```
credits_earned = hours_worked / 8
```

**Examples:**
- 8 hours worked = **1.0 credit**
- 16 hours worked = **2.0 credits**
- 4 hours worked = **0.5 credits**
- 12 hours worked = **1.5 credits**

**Validation:**
- Minimum hours: 0.5 (30 minutes)
- Maximum hours per entry: 24 hours

### FIFO (First-In-First-Out) Algorithm

When applying service credits to offset absences, the system automatically uses **the oldest credits first**.

**Example Scenario:**

Employee has 3 service credits:
```
Credit A: work_date=2024-01-15, balance=1.0
Credit B: work_date=2024-02-10, balance=1.5
Credit C: work_date=2024-03-05, balance=0.5
Total Balance: 3.0 credits
```

**Offset Request:** Apply 2.0 credits to offset absence

**FIFO Processing:**
```
1. Use Credit A (oldest): 1.0 credits → Remaining needed: 1.0
2. Use Credit B (next oldest): 1.0 credits → Remaining needed: 0.0
3. Credit C untouched

Final State:
- Credit A: balance = 0.0 (fully used)
- Credit B: balance = 0.5 (partially used)
- Credit C: balance = 0.5 (untouched)
- Employee balance: 1.0 credits remaining
```

**Implementation:**
```php
// Get available credits ordered by work_date ASC (oldest first)
$availableCredits = ServiceCredit::where('employee_id', $employeeId)
    ->where('status', 'Approved')
    ->where('credits_balance', '>', 0)
    ->orderBy('work_date', 'asc')
    ->get();

// Apply FIFO logic
foreach ($availableCredits as $credit) {
    $toUse = min($credit->credits_balance, $remainingNeeded);

    // Create offset record
    // Deduct from credit balance
    // Update employee total balance

    $remainingNeeded -= $toUse;
    if ($remainingNeeded <= 0) break;
}
```

### Credit Expiry

**Rule:** Credits expire **1 year from work_date**

**Example:**
- Work Date: January 15, 2024
- Expiry Date: **January 15, 2025**
- After expiry, status changes to `Expired` and credits cannot be used

**Automatic Expiry Check:**
```php
public function isExpired(): bool
{
    return $this->expiry_date &&
           Carbon::parse($this->expiry_date)->isPast();
}
```

### Eligibility Rules

**Who can earn service credits:**
✅ **Permanent employees** with status = 'Active'

**Who cannot earn service credits:**
❌ Contractual employees
❌ Part-time employees
❌ Inactive employees

**Validation:**
```php
if ($employee->employment_status !== 'Permanent') {
    throw new Exception('Only permanent employees can earn service credits.');
}

if (!$employee->isActive()) {
    throw new Exception('Only active employees can earn service credits.');
}
```

### Offset Application Workflow

**Step 1: Create Service Credit**
```json
POST /api/service-credits
{
  "employee_id": 1,
  "credit_type": "Summer Work",
  "work_date": "2024-01-15",
  "hours_worked": 8,
  "description": "Summer school tutoring program"
}
```

**Step 2: Approve Service Credit**
```json
PUT /api/service-credits/1/approve
{
  "remarks": "Approved by School Head"
}
```

**Step 3: Apply Credit to Offset Absence**
```json
POST /api/service-credits/apply-offset
{
  "employee_id": 1,
  "attendance_record_id": 25,
  "credits_to_use": 1.0
}
```

**System Actions:**
1. Validates employee has sufficient balance
2. Gets available credits using FIFO (oldest first)
3. Creates offset record(s)
4. Deducts from service credit balance
5. Updates employee total balance
6. Changes attendance record status to "Service Credit Used"

**Response:**
```json
{
  "success": true,
  "message": "Service credit offset applied successfully.",
  "data": {
    "credits_applied": 1.0,
    "offsets_created": 1,
    "remaining_balance": 2.0,
    "offsets": [
      {
        "service_credit_id": 5,
        "credits_used": 1.0,
        "offset_date": "2025-02-06"
      }
    ]
  }
}
```

### Revert Offset

If a mistake was made, offsets can be reverted:

```json
PUT /api/service-credits/offsets/10/revert
{
  "reason": "Wrong attendance record selected"
}
```

**System Actions:**
1. Restores credits to service_credit
2. Updates employee balance
3. Changes attendance record status back to original
4. Marks offset as "Reverted"

---

## API Endpoints

### Attendance Records (DTR)

#### List Attendance Records
```http
GET /api/attendance-records?employee_id=1&attendance_date_from=2025-02-01&attendance_date_to=2025-02-28&status=Present&per_page=15
Authorization: Bearer {token}
```

**Filters:**
- `employee_id` - Filter by employee
- `attendance_date_from` - Start date
- `attendance_date_to` - End date
- `status` - Filter by status
- `import_source` - Filter by import source
- `per_page` - Pagination (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "employee_id": 5,
      "employee": {
        "id": 5,
        "employee_number": "EMP-2024-0001",
        "full_name": "Juan Dela Cruz"
      },
      "attendance_date": "2025-02-06",
      "time_in": "07:30:00",
      "time_out": "16:30:00",
      "lunch_out": "12:00:00",
      "lunch_in": "13:00:00",
      "status": "Present",
      "undertime_hours": 0.0,
      "late_minutes": 0,
      "overtime_hours": 0.0,
      "total_work_hours": 8.0,
      "is_complete": true,
      "is_late": false,
      "has_undertime": false,
      "can_be_edited": true,
      "import_source": "CSV Upload",
      "approved_by": null,
      "approved_at": null,
      "created_at": "2025-02-06 08:00:00",
      "updated_at": "2025-02-06 08:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

#### Get Employee Attendance
```http
GET /api/attendance-records/employee/5?month=2&year=2025
Authorization: Bearer {token}
```

#### Get Monthly Summary
```http
GET /api/attendance-records/employee/5/summary?month=2&year=2025
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "employee_id": 5,
    "month": 2,
    "year": 2025,
    "total_days": 20,
    "present_days": 18,
    "absent_days": 2,
    "late_days": 3,
    "total_undertime_hours": 2.5,
    "total_overtime_hours": 4.0,
    "average_work_hours": 7.9
  }
}
```

#### Create Attendance Record
```http
POST /api/attendance-records
Authorization: Bearer {token}
Content-Type: application/json

{
  "employee_id": 5,
  "attendance_date": "2025-02-06",
  "time_in": "07:30:00",
  "time_out": "16:30:00",
  "lunch_out": "12:00:00",
  "lunch_in": "13:00:00",
  "status": "Present",
  "remarks": "Regular day"
}
```

**Auto-calculated fields:**
- `undertime_hours`
- `late_minutes`
- `overtime_hours`

#### Import CSV
```http
POST /api/attendance-records/import-csv
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [CSV file]
```

#### Update Attendance Record
```http
PUT /api/attendance-records/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "time_out": "17:00:00",
  "remarks": "Extended for meeting"
}
```

**Note:** Can only update unapproved records

#### Approve Attendance Record
```http
PUT /api/attendance-records/1/approve
Authorization: Bearer {token}
```

#### Delete Attendance Record
```http
DELETE /api/attendance-records/1
Authorization: Bearer {token}
```

**Note:** Can only delete unapproved records

### Service Credits

#### List Service Credits
```http
GET /api/service-credits?employee_id=5&credit_type=Summer Work&status=Approved&per_page=15
Authorization: Bearer {token}
```

**Filters:**
- `employee_id` - Filter by employee
- `credit_type` - Filter by type
- `status` - Filter by status
- `work_date_from` - Start date
- `work_date_to` - End date
- `expiring_soon` - Show credits expiring within 30 days

#### Get Pending Approvals
```http
GET /api/service-credits/pending
Authorization: Bearer {token}
```

#### Get Employee Service Credits
```http
GET /api/service-credits/employee/5
Authorization: Bearer {token}
```

#### Get Credit Summary
```http
GET /api/service-credits/employee/5/summary
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "employee_id": 5,
    "total_credits_earned": 15.0,
    "total_credits_used": 8.0,
    "current_balance": 7.0,
    "pending_approval": 2.0,
    "expiring_soon": 1.5,
    "by_type": {
      "Summer Work": 5.0,
      "Holiday Work": 3.0,
      "Overtime": 4.0
    }
  }
}
```

#### Create Service Credit
```http
POST /api/service-credits
Authorization: Bearer {token}
Content-Type: application/json

{
  "employee_id": 5,
  "credit_type": "Summer Work",
  "work_date": "2024-04-15",
  "hours_worked": 8,
  "description": "Summer school tutoring program"
}
```

**Auto-calculated fields:**
- `credits_earned = hours_worked / 8`
- `credits_balance = credits_earned`
- `expiry_date = work_date + 1 year`

#### Update Service Credit
```http
PUT /api/service-credits/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "hours_worked": 12,
  "description": "Extended summer program"
}
```

**Note:** Can only update Pending credits

#### Approve Service Credit
```http
PUT /api/service-credits/1/approve
Authorization: Bearer {token}
Content-Type: application/json

{
  "remarks": "Approved by School Head"
}
```

#### Reject Service Credit
```http
PUT /api/service-credits/1/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Insufficient documentation"
}
```

#### Apply Service Credit Offset (FIFO)
```http
POST /api/service-credits/apply-offset
Authorization: Bearer {token}
Content-Type: application/json

{
  "employee_id": 5,
  "attendance_record_id": 25,
  "credits_to_use": 1.0,
  "reason": "Offsetting absence on Feb 5"
}
```

#### Revert Offset
```http
PUT /api/service-credits/offsets/10/revert
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Wrong attendance record selected"
}
```

#### Delete Service Credit
```http
DELETE /api/service-credits/1
Authorization: Bearer {token}
```

**Note:** Can only delete unused Pending credits

---

## Business Rules

### Attendance Rules

1. **Unique Constraint**
   - One attendance record per employee per day
   - Enforced by database unique index

2. **Future Prevention**
   - Cannot create records with `attendance_date > today`
   - Validation: `'attendance_date' => 'before_or_equal:today'`

3. **Approval Lock**
   - Approved records cannot be edited or deleted
   - Only Admin Officer or School Head can approve

4. **Time Calculations**
   - Late: `time_in > standard_time_in`
   - Undertime: `time_out < standard_time_out`
   - Overtime: `time_out > standard_time_out`
   - Calculations use Carbon library for accuracy

5. **CSV Import**
   - Validates employee_number exists
   - Checks for duplicate dates
   - Returns detailed error report

### Service Credit Rules

1. **Eligibility**
   - Only **Permanent** employees
   - Must have status = **Active**
   - Validated before credit creation

2. **Credit Conversion**
   - **8 hours = 1.0 credit**
   - Minimum: 0.5 hours (0.0625 credits)
   - Maximum: 24 hours per entry (3.0 credits)

3. **Expiry**
   - Credits expire **1 year** after work_date
   - Expired credits cannot be used
   - Status changes to "Expired"

4. **Application**
   - **FIFO:** Oldest credits used first
   - Cannot use more than available balance
   - Can only apply to "Absent" status records

5. **Offset Rules**
   - Minimum application: 0.5 credits
   - Maximum application: 1.0 credit per absence (1 day)
   - Creates audit trail in offsets table

6. **Status Workflow**
   - **Pending** → Can edit/delete
   - **Approved** → Can use for offsets
   - **Rejected** → Cannot use
   - **Expired** → Cannot use

7. **Revert Rules**
   - Can only revert "Applied" offsets
   - Restores credits and employee balance
   - Creates audit trail

---

## CSV Import Format

### Required Columns

```csv
employee_number,attendance_date,time_in,time_out,lunch_out,lunch_in,status,remarks
```

### Column Specifications

| Column | Type | Required | Format | Notes |
|--------|------|----------|--------|-------|
| employee_number | String | Yes | EMP-YYYY-NNNN | Must exist in employees table |
| attendance_date | Date | Yes | YYYY-MM-DD | Cannot be future date |
| time_in | Time | Conditional | HH:MM:SS | Required if status = "Present" |
| time_out | Time | Conditional | HH:MM:SS | Required if status = "Present" |
| lunch_out | Time | No | HH:MM:SS | Optional |
| lunch_in | Time | No | HH:MM:SS | Optional |
| status | Enum | Yes | See below | Valid status values |
| remarks | Text | No | Max 500 chars | Optional notes |

### Valid Status Values

- `Present`
- `Absent`
- `On Leave`
- `Half-Day`
- `Holiday`
- `Weekend`

### Sample CSV

```csv
employee_number,attendance_date,time_in,time_out,lunch_out,lunch_in,status,remarks
EMP-2024-0001,2025-02-01,07:30:00,16:30:00,12:00:00,13:00:00,Present,Regular day
EMP-2024-0002,2025-02-01,07:45:00,16:15:00,12:00:00,13:00:00,Present,Late arrival
EMP-2024-0003,2025-02-01,,,,,Absent,Sick leave
EMP-2024-0004,2025-02-01,07:30:00,18:00:00,12:00:00,13:00:00,Present,Overtime for event
EMP-2024-0005,2025-02-01,,,,,Holiday,National holiday
```

### Import Error Handling

**Error Types:**
1. Employee not found
2. Duplicate attendance date
3. Invalid date format
4. Invalid time format
5. Invalid status value
6. Missing required fields

**Error Response:**
```json
{
  "total": 5,
  "success": 4,
  "failed": 1,
  "errors": [
    {
      "row": 3,
      "employee_number": "EMP-2024-9999",
      "error": "Employee not found"
    }
  ]
}
```

---

## Testing Guide

### Test Scenario 1: Create Attendance with Undertime

**Setup:**
- Employee standard_time_out = "16:30:00"

**Test:**
```bash
POST /api/attendance-records
{
  "employee_id": 1,
  "attendance_date": "2025-02-06",
  "time_in": "07:30:00",
  "time_out": "15:00:00",  # 1.5 hours early
  "status": "Present"
}
```

**Expected Result:**
- `undertime_hours = 1.5`
- `late_minutes = 0`
- `overtime_hours = 0.0`

### Test Scenario 2: FIFO Credit Application

**Setup:**
1. Create 3 service credits:
   - Credit A: work_date="2024-01-15", hours=8 (1.0 credit)
   - Credit B: work_date="2024-02-10", hours=12 (1.5 credits)
   - Credit C: work_date="2024-03-05", hours=4 (0.5 credits)
2. Approve all credits

**Test:**
```bash
POST /api/service-credits/apply-offset
{
  "employee_id": 1,
  "attendance_record_id": 5,
  "credits_to_use": 2.0
}
```

**Expected Result:**
- Credit A: balance = 0.0 (fully used)
- Credit B: balance = 0.5 (0.5 used)
- Credit C: balance = 0.5 (untouched)
- Employee balance decreased by 2.0

### Test Scenario 3: Credit Expiry

**Setup:**
- Create service credit with work_date = 1 year + 1 day ago

**Test:**
```bash
POST /api/service-credits/apply-offset
{
  "employee_id": 1,
  "attendance_record_id": 5,
  "credits_to_use": 1.0
}
```

**Expected Result:**
- Error: "No available service credits found"
- Expired credits are not included in available credits

### Test Scenario 4: CSV Import with Mixed Results

**Setup:**
- CSV file with 5 records:
  - 3 valid records
  - 1 duplicate date
  - 1 non-existent employee

**Expected Result:**
```json
{
  "total": 5,
  "success": 3,
  "failed": 2,
  "errors": [
    {"row": 2, "error": "Duplicate attendance record"},
    {"row": 5, "error": "Employee not found"}
  ]
}
```

### Test Scenario 5: Approval Lock

**Setup:**
- Create attendance record
- Approve it

**Test:**
```bash
PUT /api/attendance-records/1
{
  "time_out": "17:00:00"
}
```

**Expected Result:**
- Error 403: "Cannot edit approved attendance records"

---

## Permissions Matrix

| Permission | Admin Officer | School Head | Teacher/Staff |
|------------|--------------|-------------|---------------|
| view_attendance | ✅ | ✅ | ✅ (own only) |
| create_attendance | ✅ | ❌ | ❌ |
| edit_attendance | ✅ | ❌ | ❌ |
| approve_attendance | ✅ | ✅ | ❌ |
| upload_attendance_csv | ✅ | ❌ | ❌ |
| export_attendance | ✅ | ✅ | ❌ |
| view_service_credits | ✅ | ✅ | ❌ |
| create_service_credits | ✅ | ❌ | ❌ |
| approve_service_credits | ✅ | ✅ | ❌ |
| apply_service_credit_offset | ✅ | ❌ | ❌ |

---

## Troubleshooting

### Issue: CSV Import Fails

**Symptoms:**
- All records marked as failed
- Error: "Employee not found"

**Solution:**
1. Check employee_number format matches database
2. Ensure employees exist in system
3. Verify CSV encoding (UTF-8)

### Issue: Service Credit Not Available

**Symptoms:**
- Error: "No available service credits found"

**Solution:**
1. Check if credits are **Approved** (not Pending/Rejected)
2. Verify credits have not expired
3. Check if employee has sufficient balance

### Issue: Cannot Apply Offset

**Symptoms:**
- Error: "Insufficient service credit balance"

**Solution:**
1. Check employee balance: `GET /api/service-credits/employee/{id}/summary`
2. Verify oldest credits are not expired
3. Ensure FIFO is working correctly (check work_date ordering)

### Issue: Attendance Auto-calculations Wrong

**Symptoms:**
- Undertime/late/overtime incorrect

**Solution:**
1. Verify employee standard_time_in and standard_time_out
2. Check time format (HH:MM:SS)
3. Ensure times are not crossing midnight

---

## Best Practices

### 1. Regular CSV Imports
- Import attendance at end of each day
- Use consistent CSV format
- Keep backup of CSV files

### 2. Timely Approvals
- Approve attendance weekly
- Review service credits within 3 days
- Add remarks for audit trail

### 3. Monitor Expiring Credits
- Use `expiring_soon` filter monthly
- Notify employees of expiring credits
- Encourage usage before expiry

### 4. FIFO Understanding
- Educate employees about FIFO
- Explain oldest credits used first
- Show calculation in offset application

### 5. Error Handling
- Review CSV import errors immediately
- Fix and re-import failed records
- Document common import issues

---

## Related Documentation

- [API Reference](./API_REFERENCE.md)
- [Postman Collection](../AdminSuite_API.postman_collection.json)
- [Installation Guide](./INSTALLATION.md)
- [Quick Start](./QUICK_START.md)

---

**Last Updated:** February 6, 2025
**Version:** 1.0.0
**Module:** HR Management - DTR & Service Credits
