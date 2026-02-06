# Postman Collection Setup Guide - DTR & Service Credits

## Quick Start

### 1. Import Collection

1. Open Postman
2. Click **Import** button
3. Select file: `AdminSuite_API.postman_collection.json`
4. Collection will appear in sidebar

### 2. Configure Environment

Create a new environment with these variables:

| Variable | Value | Description |
|----------|-------|-------------|
| `base_url` | `http://localhost:8000` | Your API base URL |
| `auth_token` | (auto-set) | Bearer token from login |

**Steps:**
1. Click ⚙️ (gear icon) → **Manage Environments**
2. Click **Add**
3. Name: `AdminSuite Local`
4. Add variables above
5. Click **Add** then **Close**
6. Select environment from dropdown (top right)

### 3. Login and Get Token

1. Expand **Authentication** folder
2. Click **Login**
3. Update credentials in body if needed:
   ```json
   {
       "email": "admin@school.deped.gov.ph",
       "password": "password123"
   }
   ```
4. Click **Send**
5. Token will be **automatically saved** to `{{auth_token}}`

### 4. Test API Connection

1. Click **Health Check** request
2. Click **Send**
3. Should return:
   ```json
   {
       "status": "OK",
       "message": "AdminSuite API is running",
       "timestamp": "2025-02-06T10:30:00.000000Z"
   }
   ```

---

## Collection Structure

### 📁 Authentication
- **Login** - Get bearer token
- **Logout** - Invalidate token

### 📁 Attendance Records (DTR)
- **List Attendance Records** - Paginated list with filters
- **Get Attendance Statistics** - Overall statistics
- **Get Employee Attendance** - Filter by employee and month
- **Get Employee Monthly Summary** - Aggregated monthly stats
- **Get Attendance Record** - Single record detail
- **Create Attendance Record** - New attendance entry
- **Create Attendance with Undertime** - Example with early departure
- **Create Attendance with Late** - Example with late arrival
- **Create Absence Record** - Absence without times
- **Import Attendance CSV** - Bulk upload
- **Update Attendance Record** - Modify existing (unapproved only)
- **Approve Attendance Record** - Lock record
- **Delete Attendance Record** - Remove (unapproved only)

### 📁 Service Credits
- **List Service Credits** - Paginated list with filters
- **Get Pending Approvals** - Credits awaiting approval
- **Get Employee Service Credits** - Filter by employee
- **Get Employee Credit Summary** - Balance and stats
- **Get Service Credit** - Single credit detail
- **Create Service Credit - Summer Work** - Example: Summer program
- **Create Service Credit - Holiday Work** - Example: Holiday duty
- **Create Service Credit - Overtime** - Example: Evening work
- **Update Service Credit** - Modify pending credit
- **Approve Service Credit** - Approve and add to balance
- **Reject Service Credit** - Reject with reason
- **Apply Service Credit Offset (FIFO)** - Use credits for absence
- **Revert Service Credit Offset** - Undo offset
- **Delete Service Credit** - Remove unused credit

### 📁 Health Check
- **Health Check** - API status

---

## Usage Examples

### Example 1: Create and Approve Attendance

**Step 1: Create attendance with late arrival**
```
Request: POST /api/attendance-records
Body:
{
    "employee_id": 1,
    "attendance_date": "2025-02-06",
    "time_in": "08:00:00",
    "time_out": "16:30:00",
    "status": "Present",
    "remarks": "Traffic"
}

Response:
{
    "success": true,
    "data": {
        "id": 15,
        "late_minutes": 30,  // Auto-calculated!
        "undertime_hours": 0.0,
        "can_be_edited": true
    }
}
```

**Step 2: Approve the record**
```
Request: PUT /api/attendance-records/15/approve

Response:
{
    "success": true,
    "data": {
        "approved_by": 2,
        "approved_at": "2025-02-06 14:30:00",
        "can_be_edited": false  // Locked!
    }
}
```

### Example 2: FIFO Service Credit Application

**Setup: Create 3 service credits**

Credit A:
```json
POST /api/service-credits
{
    "employee_id": 1,
    "credit_type": "Summer Work",
    "work_date": "2024-01-15",
    "hours_worked": 8
}
// Creates 1.0 credit
```

Credit B:
```json
POST /api/service-credits
{
    "employee_id": 1,
    "credit_type": "Holiday Work",
    "work_date": "2024-02-10",
    "hours_worked": 12
}
// Creates 1.5 credits
```

Credit C:
```json
POST /api/service-credits
{
    "employee_id": 1,
    "credit_type": "Overtime",
    "work_date": "2024-03-05",
    "hours_worked": 4
}
// Creates 0.5 credits
```

**Approve all 3 credits** (use PUT /approve for each)

**Apply 2.0 credits using FIFO:**
```json
POST /api/service-credits/apply-offset
{
    "employee_id": 1,
    "attendance_record_id": 25,
    "credits_to_use": 2.0
}

Response:
{
    "success": true,
    "data": {
        "credits_applied": 2.0,
        "offsets_created": 2,
        "remaining_balance": 1.0,
        "offsets": [
            {
                "service_credit_id": 1,  // Credit A (oldest)
                "credits_used": 1.0       // Fully used
            },
            {
                "service_credit_id": 2,  // Credit B (next oldest)
                "credits_used": 1.0       // Partially used
            }
            // Credit C untouched
        ]
    }
}
```

### Example 3: CSV Import

**Step 1: Prepare CSV file**
```csv
employee_number,attendance_date,time_in,time_out,lunch_out,lunch_in,status,remarks
EMP-2024-0001,2025-02-01,07:30:00,16:30:00,12:00:00,13:00:00,Present,
EMP-2024-0002,2025-02-01,07:45:00,16:15:00,12:00:00,13:00:00,Present,Late
EMP-2024-0003,2025-02-01,,,,,Absent,Sick
```

**Step 2: Import**
```
Request: POST /api/attendance-records/import-csv
Body: multipart/form-data
file: attendance.csv

Response:
{
    "total": 3,
    "success": 3,
    "failed": 0,
    "errors": []
}
```

---

## Common Filters

### Attendance Records

```
GET /api/attendance-records?employee_id=1&attendance_date_from=2025-02-01&attendance_date_to=2025-02-28&status=Present&per_page=20
```

| Parameter | Example | Description |
|-----------|---------|-------------|
| `employee_id` | `1` | Filter by employee |
| `attendance_date_from` | `2025-02-01` | Start date |
| `attendance_date_to` | `2025-02-28` | End date |
| `status` | `Present` | Filter by status |
| `import_source` | `CSV Upload` | Filter by source |
| `per_page` | `20` | Results per page |

### Service Credits

```
GET /api/service-credits?employee_id=1&credit_type=Summer Work&status=Approved&expiring_soon=true
```

| Parameter | Example | Description |
|-----------|---------|-------------|
| `employee_id` | `1` | Filter by employee |
| `credit_type` | `Summer Work` | Filter by type |
| `status` | `Approved` | Filter by status |
| `work_date_from` | `2024-01-01` | Start date |
| `work_date_to` | `2024-12-31` | End date |
| `expiring_soon` | `true` | Expiring within 30 days |
| `per_page` | `20` | Results per page |

---

## Environment Variables Reference

| Variable | Auto-Set | Description |
|----------|----------|-------------|
| `base_url` | No | API base URL |
| `auth_token` | Yes (from login) | Bearer token |

---

## Response Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | Success | GET, PUT successful |
| 201 | Created | POST successful |
| 401 | Unauthorized | Invalid/missing token |
| 403 | Forbidden | Permission denied |
| 404 | Not Found | Resource doesn't exist |
| 422 | Validation Error | Invalid input data |
| 500 | Server Error | Server issue |

---

## Testing Checklist

Use these requests to verify the system:

- [ ] Login and get token
- [ ] Health check returns OK
- [ ] Create attendance with undertime (verify auto-calculation)
- [ ] Create attendance with late (verify auto-calculation)
- [ ] Approve attendance (verify lock)
- [ ] Try to edit approved attendance (should fail)
- [ ] Import CSV with 3 records (verify success count)
- [ ] Create service credit (verify credits_earned = hours/8)
- [ ] Approve service credit (verify status change)
- [ ] Create 3 service credits with different dates
- [ ] Apply 2.0 credits (verify FIFO - oldest used first)
- [ ] Check employee summary (verify balance)
- [ ] Revert offset (verify credits restored)

---

## Troubleshooting

### ❌ 401 Unauthorized

**Problem:** Token missing or expired

**Solution:**
1. Run **Login** request
2. Check `{{auth_token}}` is set in environment
3. Ensure Authorization header: `Bearer {{auth_token}}`

### ❌ 403 Forbidden

**Problem:** User lacks permission

**Solution:**
1. Check user role (Admin Officer, School Head, Teacher)
2. Verify permissions in database
3. Use correct user for operation

### ❌ 422 Validation Error

**Problem:** Invalid input data

**Solution:**
1. Check required fields
2. Verify date format: `YYYY-MM-DD`
3. Verify time format: `HH:MM:SS`
4. Check enum values (status, credit_type)

### ❌ "Cannot edit approved attendance records"

**Problem:** Trying to modify locked record

**Solution:**
- This is by design
- Can only edit unapproved records
- Delete and recreate if needed (before approval)

### ❌ "No available service credits found"

**Problem:** Credits expired or not approved

**Solution:**
1. Check credit status (must be "Approved")
2. Verify expiry_date is in future
3. Check credits_balance > 0

---

## Additional Resources

- 📖 [Complete DTR & Service Credits Guide](./DTR_SERVICE_CREDITS_GUIDE.md)
- 📖 [API Reference](./API_REFERENCE.md)
- 📖 [Installation Guide](./INSTALLATION.md)

---

**Last Updated:** February 6, 2025
**Collection Version:** 1.0.0
