# AdminSuite — School Administration Backend API

A Laravel 11 REST API for the Administrative Officer II (AO II) of the Department of Education (DepEd). Replaces manual logbooks, Excel files, and physical folders with a centralized system covering personnel, inventory, attendance, and finance.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| PHP | 8.3+ |
| Database | MySQL 8.0+ |
| Auth | Laravel Sanctum (Bearer tokens) |
| RBAC | Spatie Laravel Permission |
| Audit | Spatie Laravel Activitylog |
| Testing | PHPUnit (SQLite in-memory) |

## Quick Start

### Prerequisites
- PHP >= 8.3
- Composer >= 2.x
- MySQL >= 8.0

### Installation

```bash
# 1. Clone and install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_CONNECTION=mysql
DB_DATABASE=adminsuite_db
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 3. Run migrations and seeders
php artisan migrate:fresh --seed

# 4. Start development server
php artisan serve
```

API will be available at `http://localhost:8000`.

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `superadmin@deped.gov.ph` | `SuperAdmin123!` |
| School Head | `schoolhead@deped.gov.ph` | `SchoolHead123!` |
| Admin Officer | `adminofficer@deped.gov.ph` | `AdminOfficer123!` |
| Teacher/Staff | `teacher@deped.gov.ph` | `Teacher123!` |

## Features

### Authentication & Authorization
- Laravel Sanctum Bearer token authentication
- 4 roles: Super Admin, School Head, Admin Officer, Teacher/Staff
- 40+ granular permissions enforced at the route level

### HR & Personnel
- **Employee 201 Files** — full employee profiles with Philippine government IDs (TIN, GSIS, PhilHealth, Pag-IBIG)
- **Leave Requests** — 12 leave types, recommend → approve workflow, automatic credit balance calculation
- **Service Records** — employment history, promotions, transfers
- **Trainings** — seminars, workshops, LAC sessions

### Inventory & Property
- **Inventory Items** — fund source tracking (MOOE, SEF, DepEd Central), QR tagging, depreciation
- **Issuances (ICS/PAR/General)** — issue items to employees, acknowledge receipt, record returns, transfer custodianship, overdue detection
- **Requisition & Issue Slips (RIS)** — Pending → Approved → Released workflow with quantity tracking

### Attendance
- **Biometric CSV Import** — upload daily time record logs from biometric devices; supports `employee_number,datetime` or separate `date,time` columns
- **DTR Computation** — auto-calculates time-in, time-out, hours worked, late minutes, undertime; respects holidays and weekends
- **Import Batches** — each upload is tracked with status, record count, and error count

### Government Reports (JSON payloads)
| Endpoint | Report |
|----------|--------|
| `GET /api/reports/form6/{uuid}` | Leave request (CSC Form 6) |
| `GET /api/reports/ris/{uuid}` | Requisition & Issue Slip |
| `GET /api/reports/dv/{uuid}` | Disbursement Voucher |
| `GET /api/reports/iar/{uuid}` | Inspection & Acceptance Report |
| `GET /api/reports/pds/{uuid}` | Personal Data Sheet |

### Financial Management
- Budget allocation tracking (SIP/AIP)
- Expense tracking with approval workflow
- Supplier and procurement management
- Purchase orders and delivery tracking
- Liquidation and cash advance management

## API Endpoints

All routes require `Authorization: Bearer {token}` except `/api/auth/login`.

### Authentication
```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/user
```

### User Management
```
GET    /api/users
POST   /api/users
GET    /api/users/{uuid}
PUT    /api/users/{uuid}
DELETE /api/users/{uuid}
POST   /api/users/{uuid}/reset-password
POST   /api/users/{uuid}/assign-role
GET    /api/users/statistics
POST   /api/users/{uuid}/permissions
```

### Employees
```
GET    /api/employees
POST   /api/employees
GET    /api/employees/{uuid}
PUT    /api/employees/{uuid}
DELETE /api/employees/{uuid}
POST   /api/employees/{uuid}/promote
```

### Leave Requests
```
GET    /api/leave-requests
POST   /api/leave-requests
GET    /api/leave-requests/{uuid}
PUT    /api/leave-requests/{uuid}
PUT    /api/leave-requests/{uuid}/recommend
PUT    /api/leave-requests/{uuid}/approve
PUT    /api/leave-requests/{uuid}/disapprove
DELETE /api/leave-requests/{uuid}
```

### Service Records & Trainings
```
GET    /api/service-records
POST   /api/service-records
GET    /api/service-records/{uuid}
PUT    /api/service-records/{uuid}
DELETE /api/service-records/{uuid}

GET    /api/trainings
POST   /api/trainings
GET    /api/trainings/{uuid}
PUT    /api/trainings/{uuid}
DELETE /api/trainings/{uuid}
```

### Inventory Items
```
GET    /api/inventory-items
POST   /api/inventory-items
GET    /api/inventory-items/{uuid}
PUT    /api/inventory-items/{uuid}
DELETE /api/inventory-items/{uuid}
GET    /api/inventory-items/statistics
```

### Issuances
```
GET    /api/issuances
POST   /api/issuances
GET    /api/issuances/{uuid}
PUT    /api/issuances/{uuid}
DELETE /api/issuances/{uuid}
POST   /api/issuances/batch
PUT    /api/issuances/{uuid}/acknowledge
PUT    /api/issuances/{uuid}/return
PUT    /api/issuances/{uuid}/transfer
GET    /api/issuances/overdue
GET    /api/issuances/employee/{uuid}
GET    /api/issuances/statistics
```

### Requisition & Issue Slips
```
GET    /api/requisition-slips
POST   /api/requisition-slips
GET    /api/requisition-slips/{uuid}
PUT    /api/requisition-slips/{uuid}
DELETE /api/requisition-slips/{uuid}
PUT    /api/requisition-slips/{uuid}/approve
PUT    /api/requisition-slips/{uuid}/release
PUT    /api/requisition-slips/{uuid}/cancel
GET    /api/requisition-slips/pending
GET    /api/requisition-slips/statistics
```

### Attendance
```
POST   /api/attendance/import
GET    /api/attendance/import-batches
GET    /api/attendance/dtr/{uuid}
GET    /api/attendance/dtr/{uuid}/summary
PATCH  /api/attendance/dtr/{uuid}/{date}
```

### Reports
```
GET    /api/reports/form6/{uuid}
GET    /api/reports/ris/{uuid}
GET    /api/reports/dv/{uuid}
GET    /api/reports/iar/{uuid}
GET    /api/reports/pds/{uuid}
```

### Financial
```
GET    /api/budgets
POST   /api/budgets
GET    /api/expenses
POST   /api/expenses
GET    /api/suppliers
POST   /api/suppliers
GET    /api/procurements
POST   /api/procurements
GET    /api/purchase-orders
POST   /api/purchase-orders
GET    /api/deliveries
POST   /api/deliveries
GET    /api/cash-advances
POST   /api/cash-advances
GET    /api/liquidations
POST   /api/liquidations
```

## Architecture

**Service-Repository Pattern:**

```
Request → Controller → FormRequest (validation)
                    → Service (business logic)
                    → Repository (data access)
                    → Model
```

All public-facing IDs are **UUIDs**. Controllers resolve `uuid → integer id` before passing to the service layer. Internal joins always use integer primary keys.

## Testing

```bash
php artisan test
```

Tests use **SQLite in-memory** with `RefreshDatabase`. The `RoleAndPermissionSeeder` runs before each test to ensure roles and permissions exist. The suite covers 99+ tests across authentication, HR, inventory, attendance, and reports.

## Filipino Context

- Employee names in Filipino format (Juan dela Cruz, Maria Santos)
- DepEd-specific positions (Teacher I-III, Master Teacher, Head Teacher VI, Principal IV)
- Philippine government IDs: TIN, GSIS, PhilHealth, Pag-IBIG
- DepEd fund sources: MOOE, SEF, DepEd Central
- Philippine address format (Brgy., City/Municipality, Province, ZIP)
- DepEd inventory items (Epson L3110 Printer, Bond Paper Sub 20)
