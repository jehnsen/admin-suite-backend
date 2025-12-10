# AdminSuite API - Postman Testing Guide

Complete guide for testing the AdminSuite API using Postman.

## 📦 Import Collection & Environment

### Step 1: Import the Collection

1. Open Postman
2. Click **Import** button (top left)
3. Select `AdminSuite_API.postman_collection.json`
4. Click **Import**

### Step 2: Import the Environment

1. Click **Import** again
2. Select `AdminSuite_Environment.postman_environment.json`
3. Click **Import**

### Step 3: Select Environment

1. Click the environment dropdown (top right)
2. Select **"AdminSuite - Local Development"**

---

## 🚀 Quick Start Testing Flow

### 1. Login (Get Authentication Token)

**Request:** `Authentication > Login`

**Body:**
```json
{
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
}
```

**What happens:**
- ✅ Returns Bearer token
- ✅ Auto-saves token to environment variable `{{auth_token}}`
- ✅ Auto-saves user ID to `{{user_id}}`

**Response Example:**
```json
{
    "message": "Login successful",
    "token": "1|xxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 3,
        "name": "Jose Protacio Rizal",
        "email": "adminofficer@deped.gov.ph",
        "roles": ["Admin Officer"],
        "permissions": [...]
    }
}
```

---

### 2. Test Protected Endpoints

After login, all endpoints use `{{auth_token}}` automatically via Bearer Auth.

#### Get All Employees

**Request:** `HR - Employees > Get All Employees`

**Query Parameters:**
- `per_page=15` - Items per page
- `status=Active` - Filter by status
- `position=Teacher I` - Filter by position (optional)
- `search=Juan` - Search by name (optional)

#### Create New Employee

**Request:** `HR - Employees > Create Employee`

**Body:** (Sample Filipino employee data included)

**Auto-saves:** Employee ID to `{{employee_id}}`

#### Create Leave Request

**Request:** `HR - Leave Requests > Create Leave Request`

**Body:**
```json
{
    "employee_id": 3,
    "leave_type": "Vacation Leave",
    "start_date": "2024-12-20",
    "end_date": "2024-12-22",
    "reason": "Christmas vacation with family"
}
```

**Features:**
- ✅ Auto-calculates working days
- ✅ Validates leave credits
- ✅ Checks for overlapping leaves

---

## 🔐 Default Test Credentials

All credentials follow the format: `{role}@deped.gov.ph`

| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| **Super Admin** | superadmin@deped.gov.ph | SuperAdmin123! | All permissions |
| **School Head** | schoolhead@deped.gov.ph | SchoolHead123! | Management level |
| **Admin Officer** | adminofficer@deped.gov.ph | AdminOfficer123! | Full admin access |
| **Teacher** | teacher@deped.gov.ph | Teacher123! | Limited access |

---

## 📋 Testing Workflow Examples

### Example 1: Complete Leave Request Flow

1. **Login as Admin Officer**
   - `Authentication > Login`
   - Use: `adminofficer@deped.gov.ph`

2. **Create Leave Request**
   - `HR - Leave Requests > Create Leave Request`
   - Employee ID: 3

3. **Recommend Leave** (as immediate supervisor)
   - `HR - Leave Requests > Recommend Leave`
   - Leave ID: 1
   - Recommended by: 2

4. **Approve Leave** (as School Head)
   - Login as School Head first
   - `HR - Leave Requests > Approve Leave`
   - Leave ID: 1
   - **Result:** Leave credits auto-deducted!

5. **Check Employee Leave Credits**
   - `HR - Employees > Get Employee by ID`
   - See updated `vacation_leave_credits`

### Example 2: Employee Promotion Flow

1. **Get Current Employee Data**
   - `HR - Employees > Get Employee by ID`
   - ID: 3

2. **Get Current Service Record**
   - `HR - Service Records > Get Service Records by Employee`
   - Employee ID: 3

3. **Promote Employee**
   - `HR - Employees > Promote Employee`
   - **Result:**
     - ✅ Employee position updated
     - ✅ Old service record closed (date_to set)
     - ✅ New service record created

4. **Verify Promotion**
   - Check service records again
   - Should see 2 records now

---

## 🧪 Collection Features

### Auto-Saved Environment Variables

The collection automatically saves these to environment:

- `{{auth_token}}` - After login
- `{{user_id}}` - After login/register
- `{{employee_id}}` - After creating employee
- `{{leave_request_id}}` - After creating leave request

### Pre-configured Headers

All requests include:
- `Accept: application/json`
- `Authorization: Bearer {{auth_token}}` (protected routes)
- `Content-Type: application/json` (POST/PUT requests)

### Query Parameters

Many endpoints include pre-configured query params:
- Filters (status, position, leave_type)
- Pagination (per_page)
- Search (search term)

---

## 📊 Endpoint Categories

### 1. Authentication (3 endpoints)
- ✅ Register User
- ✅ Login (auto-saves token)
- ✅ Logout

### 2. HR - Employees (8 endpoints)
- ✅ Get All (with filters)
- ✅ Get by ID
- ✅ Create
- ✅ Update
- ✅ Delete
- ✅ Promote (special workflow)
- ✅ Search
- ✅ Statistics

### 3. HR - Leave Requests (10 endpoints)
- ✅ Get All (with filters)
- ✅ Get by ID
- ✅ Create (Vacation Leave)
- ✅ Create (Sick Leave - with illness details)
- ✅ Recommend
- ✅ Approve (auto-deducts credits)
- ✅ Disapprove
- ✅ Cancel (auto-restores credits)
- ✅ Get Pending
- ✅ Statistics

### 4. HR - Service Records (5 endpoints)
- ✅ Get by Employee (201 file)
- ✅ Get by ID
- ✅ Create
- ✅ Update
- ✅ Delete

### 5. Utilities (2 endpoints)
- ✅ Health Check
- ✅ API Root

**Total: 28 endpoints**

---

## 🎯 Testing Scenarios

### Scenario 1: Insufficient Leave Credits

**Test:**
1. Login as employee with low credits
2. Create leave request for 20 days
3. **Expected:** Error message about insufficient credits

### Scenario 2: Overlapping Leave Requests

**Test:**
1. Create leave request (Dec 20-22)
2. Create another leave for same dates
3. **Expected:** Error about overlapping leaves

### Scenario 3: Leave Approval Workflow

**Complete Flow:**
```
Create → Pending
  ↓
Recommend → Recommended (by immediate supervisor)
  ↓
Approve → Approved (by School Head)
  ↓
Credits Deducted ✅
```

### Scenario 4: Leave Cancellation

**Test:**
1. Create and approve a leave
2. Check employee credits (should be deducted)
3. Cancel the leave
4. Check credits again (should be restored)

---

## 🔍 Response Examples

### Successful Employee Creation
```json
{
    "message": "Employee created successfully.",
    "data": {
        "id": 7,
        "employee_number": "EMP-2024-0007",
        "full_name": "Pedro Cruz Santos",
        "position": "Teacher I",
        "status": "Active",
        "vacation_leave_credits": 15.00,
        "sick_leave_credits": 15.00
    }
}
```

### Leave Approval Success
```json
{
    "message": "Leave request approved successfully.",
    "data": {
        "id": 1,
        "leave_type": "Vacation Leave",
        "days_requested": 3.00,
        "status": "Approved",
        "approved_by": {
            "id": 1,
            "full_name": "Maria Clara Reyes Santos"
        },
        "approved_at": "2024-12-09 16:30:00"
    }
}
```

### Validation Error
```json
{
    "message": "Insufficient vacation leave credits. Available: 10.5 days."
}
```

---

## 📝 Tips & Best Practices

### 1. Always Check Health First
Before testing, verify API is running:
```
GET http://localhost:8000/api/health
```

### 2. Use Console for Debugging
- Check Postman Console (bottom left)
- See auto-saved environment variables
- View full request/response

### 3. Test Different Roles
Login with different credentials to test permissions:
- Super Admin → Full access
- School Head → Approval permissions
- Admin Officer → CRUD operations
- Teacher → Limited view access

### 4. Chain Requests
Use the auto-saved IDs:
1. Create employee → saves `{{employee_id}}`
2. Create leave for that employee using `{{employee_id}}`
3. Approve using admin credentials

### 5. Reset Database
If needed:
```bash
php artisan migrate:fresh --seed
```
Then re-login to get new token.

---

## 🐛 Troubleshooting

### Error: "Unauthenticated"
**Fix:** Login again to get fresh token

### Error: "This action is unauthorized"
**Fix:** Login with account that has proper permissions (Admin Officer or School Head)

### Error: "Employee not found"
**Fix:** Check employee ID exists using "Get All Employees"

### Error: "SQLSTATE[HY000]"
**Fix:** Ensure database is running (XAMPP MySQL)

---

## 📚 Additional Resources

- **API Documentation:** http://localhost:8000/docs (after running `php artisan scribe:generate`)
- **Architecture:** See `ARCHITECTURE.md`
- **Installation:** See `SETUP_GUIDE.md`

---

## ✅ Quick Checklist

Before testing:
- [ ] XAMPP MySQL running
- [ ] Laravel server running (`php artisan serve`)
- [ ] Database seeded (`php artisan db:seed`)
- [ ] Postman collection imported
- [ ] Environment selected in Postman
- [ ] Logged in (token saved)

**Happy Testing! 🚀**
