# AdminSuite - School Management System Backend API

## Project Overview

**AdminSuite** is a comprehensive Enterprise Resource Planning (ERP) backend API designed specifically for DepEd (Department of Education) Administrative Officers in the Philippines. This system manages all aspects of school administration including HR, inventory, and financial management.

### Technology Stack

- **Framework:** Laravel 11
- **PHP Version:** 8.3+
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum (API Token-based)
- **Authorization:** Spatie Laravel Permission (RBAC)
- **API Documentation:** Scribe
- **Architecture:** Service-Repository Pattern

---

## Architecture Pattern

The project follows a strict **Service-Repository Pattern** for clean separation of concerns:

```
┌─────────────┐
│  Controller │ ← HTTP Layer (Validation, Response Formatting)
└──────┬──────┘
       │
┌──────▼──────┐
│   Service   │ ← Business Logic Layer
└──────┬──────┘
       │
┌──────▼──────┐
│ Repository  │ ← Data Access Layer (Eloquent/DB Queries)
└──────┬──────┘
       │
┌──────▼──────┐
│    Model    │ ← Eloquent Models
└─────────────┘
```

### Layer Responsibilities

1. **Controllers** ([app/Http/Controllers/Api/](app/Http/Controllers/Api/))
   - Handle HTTP requests and responses
   - Validate input using FormRequests
   - Format output using API Resources
   - **NO business logic**

2. **Services** ([app/Services/](app/Services/))
   - Implement business logic
   - Handle complex calculations (e.g., leave credits)
   - Manage multi-model transactions
   - Orchestrate repository operations

3. **Repositories** ([app/Repositories/](app/Repositories/))
   - Execute database queries
   - Perform CRUD operations
   - Implement interfaces

4. **Interfaces** ([app/Interfaces/](app/Interfaces/))
   - Define repository contracts
   - Enable dependency injection

---

## Core Modules

### 1. Authentication & Authorization

**Location:** [app/Http/Controllers/Api/Auth/](app/Http/Controllers/Api/Auth/)

**Features:**
- User registration and login via Sanctum tokens
- Role-Based Access Control (RBAC) using Spatie Laravel Permission
- Four pre-defined roles:
  - **Super Admin** - Full system access
  - **School Head** - Management-level access
  - **Admin Officer** - Administrative operations (System Owner)
  - **Teacher/Staff** - Limited access

**Permissions:** 30+ granular permissions (see [database/seeders/RoleAndPermissionSeeder.php](database/seeders/RoleAndPermissionSeeder.php))

**Endpoints:**
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
```

---

### 2. HR Management

**Location:** [app/Services/HR/](app/Services/HR/)

#### 2.1 Employee Management

**Features:**
- Employee master data (201 files)
- Automatic employee number generation
- Leave credits calculation (1.25 days/month for permanent employees)
- Employee promotion with automatic service record creation
- Position tracking and salary grade management

**Endpoints:**
```
GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}
POST   /api/employees/{id}/promote
GET    /api/employees/statistics
GET    /api/employees/search
```

**Business Logic Highlights:**
- [EmployeeService.php:58](app/Services/HR/EmployeeService.php#L58) - `createEmployee()` with initial service record
- [EmployeeService.php:91](app/Services/HR/EmployeeService.php#L91) - `promoteEmployee()` with service record management
- [EmployeeService.php:143](app/Services/HR/EmployeeService.php#L143) - `updateMonthlyLeaveCredits()` for 1.25 days/month accrual

#### 2.2 Leave Request Management

**Features:**
- 12 leave types (Vacation, Sick, Maternity, Paternity, Special Privilege, etc.)
- Automatic working days calculation (excludes weekends)
- Leave credit validation before approval
- Multi-stage approval workflow (Recommend → Approve)
- Automatic credit deduction on approval
- Automatic credit restoration on cancellation

**Endpoints:**
```
GET    /api/leave-requests
POST   /api/leave-requests
GET    /api/leave-requests/{id}
PUT    /api/leave-requests/{id}/recommend
PUT    /api/leave-requests/{id}/approve
PUT    /api/leave-requests/{id}/disapprove
PUT    /api/leave-requests/{id}/cancel
GET    /api/leave-requests/pending
GET    /api/leave-requests/statistics
```

**Business Logic Highlights:**
- [LeaveRequestService.php:34](app/Services/HR/LeaveRequestService.php#L34) - `createLeaveRequest()` with validation
- [LeaveRequestService.php:69](app/Services/HR/LeaveRequestService.php#L69) - `approveLeaveRequest()` with credit deduction
- [LeaveRequestService.php:185](app/Services/HR/LeaveRequestService.php#L185) - `calculateWorkingDays()` excluding weekends

#### 2.3 Service Records (201 Files)

**Features:**
- Complete employment history tracking
- Promotion/transfer/reassignment records
- Salary progression tracking
- Government service calculation

**Endpoints:**
```
GET    /api/service-records/employee/{employeeId}
POST   /api/service-records
GET    /api/service-records/{id}
PUT    /api/service-records/{id}
DELETE /api/service-records/{id}
```

---

### 3. Property & Supply (Inventory)

**Database Tables:**
- [2024_01_01_000004_create_inventory_items_table.php](database/migrations/2024_01_01_000004_create_inventory_items_table.php)
- [2024_01_01_000005_create_issuances_table.php](database/migrations/2024_01_01_000005_create_issuances_table.php)

**Features:**
- Asset/property tracking with serial numbers
- Fund source tracking (MOOE, SEF, DepEd Central, LGU, Donation)
- Condition monitoring (Serviceable, Unserviceable, For Repair, For Disposal)
- Custodianship tracking via Issuances
- Depreciation calculation
- Property accountability records

**Sample Items (Filipino Context):**
- Epson L3110 Printer
- HP Laptop 14-inch
- Canon ImageClass MF244dw
- Bond Paper Sub 20
- Office Table - Executive
- Whiteboard Markers

---

### 4. Financial Management

**Database Tables:**
- [2024_01_01_000006_create_budgets_table.php](database/migrations/2024_01_01_000006_create_budgets_table.php)
- [2024_01_01_000007_create_expenses_table.php](database/migrations/2024_01_01_000007_create_expenses_table.php)

**Features:**
- Budget allocation tracking (SIP/AIP)
- Real-time budget utilization monitoring
- Expense tracking with approval workflow
- Fund source management (MOOE, SEF, DepEd Central)
- Liquidation tracking for cash advances
- Automatic budget balance calculation

**Business Logic:**
- [Budget.php:37](app/Models/Budget.php#L37) - Auto-calculate remaining balance
- [Budget.php:131](app/Models/Budget.php#L131) - Calculate utilization percentage
- [Budget.php:153](app/Models/Budget.php#L153) - Check if budget is nearly depleted (90%)

---

## Database Schema

### Core Tables

1. **users** - System authentication
2. **roles** - User roles (Spatie)
3. **permissions** - Granular permissions (Spatie)
4. **employees** - Employee master data (72 fields)
5. **leave_requests** - Leave applications with workflow
6. **service_records** - Employment history (201 files)
7. **inventory_items** - Asset tracking
8. **issuances** - Custodianship records
9. **budgets** - Budget allocations (SIP/AIP)
10. **expenses** - Disbursement tracking

### Key Relationships

```
User 1-to-1 Employee
Employee 1-to-Many LeaveRequests
Employee 1-to-Many ServiceRecords
Employee 1-to-Many Issuances (as custodian)
InventoryItem 1-to-Many Issuances
Budget 1-to-Many Expenses
Employee Many-to-Many Roles
Employee Many-to-Many Permissions
```

---

## Security Implementation

### Authentication Flow

1. User registers via `/api/auth/register` → Receives Sanctum token
2. User logs in via `/api/auth/login` → Receives Sanctum token
3. All API requests include `Authorization: Bearer {token}` header
4. Token validated via `auth:sanctum` middleware

### Authorization Flow

1. **Route Protection:** `auth:sanctum` middleware
2. **Permission Checks:** `permission:view_employees` middleware
3. **Role Checks:** Available via `role:Admin Officer` middleware

**Example Route Protection:**
```php
Route::get('/employees', [EmployeeController::class, 'index'])
    ->middleware(['auth:sanctum', 'permission:view_employees']);
```

---

## API Documentation

### Endpoints Overview

**Authentication:**
- Register, Login, Logout

**Employees:**
- CRUD operations
- Promotion workflow
- Statistics and search

**Leave Requests:**
- CRUD operations
- Approval workflow (Recommend → Approve → Disapprove)
- Automatic credit management

**Service Records:**
- Employment history tracking
- Position changes

**Inventory:**
- Asset management
- Issuance tracking

**Financial:**
- Budget management
- Expense tracking with approval

### Accessing Documentation

After running `php artisan scribe:generate`, access API docs at:
```
http://localhost:8000/docs
```

---

## Sample Data (Filipino Context)

### Users & Employees

1. **Maria Clara Santos** - Principal IV (School Head)
   - Email: schoolhead@deped.gov.ph
   - Password: SchoolHead123!

2. **Jose Protacio Rizal** - Administrative Officer IV
   - Email: adminofficer@deped.gov.ph
   - Password: AdminOfficer123!

3. **Juan dela Cruz** - Teacher III
   - Email: teacher@deped.gov.ph
   - Password: Teacher123!

### Inventory Items

- Epson L3110 Printer (MOOE-funded)
- HP Laptop 14-inch (DepEd Central)
- Bond Paper Sub 20 (50 reams)
- Office Table - Executive (SEF-funded)
- Canon ImageClass MF244dw

### Budgets

- MOOE-2024-001: ₱500,000 (Operating Expenses)
- SEF-2024-001: ₱1,000,000 (Infrastructure)
- MOOE-2024-002: ₱250,000 (Training and Development)
- MOOE-2024-003: ₱300,000 (ICT Equipment)

---

## Installation & Setup

### 1. Install Laravel & Dependencies

```bash
composer create-project laravel/laravel ao-suite-backend "11.*"
cd ao-suite-backend
composer require laravel/sanctum spatie/laravel-permission knuckleswtf/scribe
```

### 2. Configure Environment

Edit `.env`:
```env
APP_NAME="AdminSuite API"
DB_CONNECTION=mysql
DB_DATABASE=adminsuite_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Publish Vendor Assets

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 4. Run Migrations & Seeders

```bash
php artisan migrate:fresh --seed
```

This will:
- Create all database tables
- Seed roles and permissions
- Create 4 default users
- Create 6 sample employees with Filipino names
- Create service records, leave requests
- Create inventory items with Filipino context
- Create budgets and expenses

### 5. Start Development Server

```bash
php artisan serve
```

API available at: `http://localhost:8000`

---

## Testing the API

### 1. Login

```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
}
```

### 2. Get Employees

```bash
GET http://localhost:8000/api/employees
Authorization: Bearer {your-token-here}
```

### 3. Create Leave Request

```bash
POST http://localhost:8000/api/leave-requests
Authorization: Bearer {your-token-here}
Content-Type: application/json

{
    "employee_id": 3,
    "leave_type": "Vacation Leave",
    "start_date": "2024-12-20",
    "end_date": "2024-12-22",
    "reason": "Christmas vacation with family"
}
```

---

## File Structure

```
ao-suite-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── Auth/
│   │   │       │   ├── LoginController.php
│   │   │       │   ├── RegisterController.php
│   │   │       │   └── LogoutController.php
│   │   │       └── HR/
│   │   │           ├── EmployeeController.php
│   │   │           ├── LeaveRequestController.php
│   │   │           └── ServiceRecordController.php
│   │   ├── Requests/
│   │   │   └── HR/
│   │   │       ├── StoreEmployeeRequest.php
│   │   │       ├── UpdateEmployeeRequest.php
│   │   │       ├── StoreLeaveRequestRequest.php
│   │   │       └── StoreServiceRecordRequest.php
│   │   └── Resources/
│   │       └── HR/
│   │           ├── EmployeeResource.php
│   │           ├── LeaveRequestResource.php
│   │           └── ServiceRecordResource.php
│   ├── Services/
│   │   └── HR/
│   │       ├── EmployeeService.php
│   │       ├── LeaveRequestService.php
│   │       └── ServiceRecordService.php
│   ├── Repositories/
│   │   └── HR/
│   │       ├── EmployeeRepository.php
│   │       ├── LeaveRequestRepository.php
│   │       └── ServiceRecordRepository.php
│   ├── Interfaces/
│   │   └── HR/
│   │       ├── EmployeeRepositoryInterface.php
│   │       ├── LeaveRequestRepositoryInterface.php
│   │       └── ServiceRecordRepositoryInterface.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Employee.php
│   │   ├── LeaveRequest.php
│   │   ├── ServiceRecord.php
│   │   ├── InventoryItem.php
│   │   ├── Issuance.php
│   │   ├── Budget.php
│   │   └── Expense.php
│   └── Providers/
│       └── RepositoryServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_employees_table.php
│   │   ├── 2024_01_01_000002_create_leave_requests_table.php
│   │   ├── 2024_01_01_000003_create_service_records_table.php
│   │   ├── 2024_01_01_000004_create_inventory_items_table.php
│   │   ├── 2024_01_01_000005_create_issuances_table.php
│   │   ├── 2024_01_01_000006_create_budgets_table.php
│   │   └── 2024_01_01_000007_create_expenses_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleAndPermissionSeeder.php
│       ├── UserSeeder.php
│       ├── EmployeeSeeder.php
│       ├── ServiceRecordSeeder.php
│       ├── LeaveRequestSeeder.php
│       ├── InventoryItemSeeder.php
│       ├── IssuanceSeeder.php
│       ├── BudgetSeeder.php
│       └── ExpenseSeeder.php
├── routes/
│   └── api.php
├── ARCHITECTURE.md
├── INSTALLATION.md
└── PROJECT_SUMMARY.md
```

---

## Key Business Logic Examples

### Leave Credit Calculation

**Location:** [EmployeeService.php:143](app/Services/HR/EmployeeService.php#L143)

```php
public function updateMonthlyLeaveCredits(): array
{
    $activeEmployees = $this->employeeRepository->getActiveEmployees();

    foreach ($activeEmployees as $employee) {
        if ($employee->employment_status === 'Permanent') {
            // DepEd Rule: 1.25 days per month for both VL and SL
            $newVacationCredits = $employee->vacation_leave_credits + 1.25;
            $newSickCredits = $employee->sick_leave_credits + 1.25;

            $this->employeeRepository->updateLeaveCredits(
                $employee->id,
                $newVacationCredits,
                $newSickCredits
            );
        }
    }
}
```

### Leave Approval with Credit Deduction

**Location:** [LeaveRequestService.php:69](app/Services/HR/LeaveRequestService.php#L69)

```php
public function approveLeaveRequest(int $id, int $approvedBy, ?string $remarks = null): LeaveRequest
{
    return DB::transaction(function () use ($id, $approvedBy, $remarks) {
        $leaveRequest = $this->leaveRequestRepository->findLeaveRequestById($id);

        // Deduct leave credits from employee
        $this->employeeService->deductLeaveCredits(
            $leaveRequest->employee_id,
            $leaveRequest->leave_type,
            $leaveRequest->days_requested
        );

        // Update status to Approved
        return $this->leaveRequestRepository->updateLeaveRequestStatus($id, 'Approved', [
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_remarks' => $remarks,
        ]);
    });
}
```

### Employee Promotion

**Location:** [EmployeeService.php:91](app/Services/HR/EmployeeService.php#L91)

```php
public function promoteEmployee(int $employeeId, array $promotionData): Employee
{
    return DB::transaction(function () use ($employeeId, $promotionData) {
        // 1. Close current service record
        $this->serviceRecordRepository->closeCurrentServiceRecord(
            $employeeId,
            $promotionData['effective_date']
        );

        // 2. Update employee master data
        $this->employeeRepository->updateEmployee($employeeId, [
            'position' => $promotionData['new_position'],
            'salary_grade' => $promotionData['new_salary_grade'],
            'monthly_salary' => $promotionData['new_monthly_salary'],
        ]);

        // 3. Create new service record for promotion
        $this->serviceRecordRepository->createServiceRecord([
            'employee_id' => $employeeId,
            'date_from' => $promotionData['effective_date'],
            'designation' => $promotionData['new_position'],
            'action_type' => 'Promotion',
            // ... other fields
        ]);

        return $employee->fresh();
    });
}
```

---

## DepEd-Specific Features

### 1. Plantilla Item Numbers
Each employee has a unique Plantilla Item Number tracking their official position allocation.

### 2. Leave Types
12 leave types compliant with CSC (Civil Service Commission) guidelines:
- Vacation Leave, Sick Leave
- Maternity/Paternity Leave
- Special Privilege Leave
- Solo Parent Leave
- VAWC Leave (Violence Against Women and Children)
- Study Leave, Rehabilitation Leave
- And more...

### 3. Salary Grades
Supports Salary Grades 1-33 with step increments 1-8, following government salary standardization.

### 4. Fund Sources
Tracks fund sources specific to DepEd:
- **MOOE** (Maintenance and Other Operating Expenses)
- **SEF** (Special Education Fund)
- **DepEd Central**
- **LGU** (Local Government Unit)
- **Donations**

### 5. Budget Classifications
- **SIP** (School Improvement Plan)
- **AIP** (Annual Implementation Plan)
- **GAA** (General Appropriations Act)

---

## Next Steps

### Extending the System

1. **Add Inventory Module Controllers** (structure already prepared)
2. **Add Financial Module Controllers** (structure already prepared)
3. **Implement Reports Module** (PDF generation, analytics)
4. **Add File Upload Support** (for attachments, signatures)
5. **Implement Notifications** (email/SMS for leave approvals)
6. **Add Dashboard Analytics** (charts, KPIs)
7. **Implement Queue System** (for batch operations like monthly leave credits)

### Production Deployment

1. Configure proper `.env` for production
2. Set up SSL/TLS certificates
3. Configure queue workers
4. Set up database backups
5. Implement logging and monitoring
6. Configure rate limiting
7. Set up CI/CD pipeline

---

## Support & Documentation

- **Installation Guide:** [INSTALLATION.md](INSTALLATION.md)
- **Architecture Details:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API Documentation:** `/docs` (after running `php artisan scribe:generate`)

---

## Credits

Built with ❤️ for DepEd Administrative Officers using Laravel 11, following enterprise-grade software architecture patterns.

**License:** MIT
**Version:** 1.0.0
**PHP Version:** 8.3+
**Laravel Version:** 11+
