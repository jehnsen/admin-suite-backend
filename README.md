# AdminSuite - School Management System Backend API

A comprehensive Enterprise Resource Planning (ERP) backend API for DepEd Administrative Officers in the Philippines.

## Quick Start

### Prerequisites
- PHP >= 8.3
- Composer >= 2.x
- MySQL >= 8.0

### Installation

1. **Install Laravel 11 and Dependencies:**
```bash
composer create-project laravel/laravel ao-suite-backend "11.*"
cd ao-suite-backend
composer require laravel/sanctum spatie/laravel-permission knuckleswtf/scribe
```

2. **Configure Environment:**
```bash
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

3. **Publish Vendor Assets:**
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

4. **Run Migrations and Seeders:**
```bash
php artisan migrate:fresh --seed
```

5. **Start Development Server:**
```bash
php artisan serve
```

API will be available at: `http://localhost:8000`

## Features

### = Authentication & Authorization
- Laravel Sanctum API token authentication
- Role-Based Access Control (RBAC)
- 4 pre-defined roles: Super Admin, School Head, Admin Officer, Teacher/Staff
- 30+ granular permissions

### =e HR Management
- **Employee Management:** Complete 201 file system with Filipino employee data
- **Leave Requests:** 12 leave types with approval workflow and automatic credit calculation
- **Service Records:** Employment history tracking with promotions and transfers

### =� Inventory Management
- Asset tracking with serial numbers and property numbers
- Fund source tracking (MOOE, SEF, DepEd Central)
- Custodianship via Issuances
- Depreciation calculation

### =� Financial Management
- Budget allocation tracking (SIP/AIP)
- Real-time utilization monitoring
- Expense tracking with approval workflow
- Liquidation tracking

## Default Credentials

After seeding, use these credentials to test:

**Super Admin:**
- Email: `superadmin@deped.gov.ph`
- Password: `SuperAdmin123!`

**School Head:**
- Email: `schoolhead@deped.gov.ph`
- Password: `SchoolHead123!`

**Admin Officer:**
- Email: `adminofficer@deped.gov.ph`
- Password: `AdminOfficer123!`

**Teacher:**
- Email: `teacher@deped.gov.ph`
- Password: `Teacher123!`

## API Endpoints

### Authentication
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
```

### Employees
```
GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}
POST   /api/employees/{id}/promote
```

### Leave Requests
```
GET    /api/leave-requests
POST   /api/leave-requests
PUT    /api/leave-requests/{id}/recommend
PUT    /api/leave-requests/{id}/approve
PUT    /api/leave-requests/{id}/disapprove
```

## Testing the API

1. **Login:**
```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
}
```

2. **Get Employees (use token from login):**
```bash
GET http://localhost:8000/api/employees
Authorization: Bearer {your-token}
```

## Architecture

This project follows the **Service-Repository Pattern**:

- **Controllers:** HTTP layer (validation, response formatting)
- **Services:** Business logic layer
- **Repositories:** Data access layer
- **Interfaces:** Repository contracts

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architecture documentation.

## Documentation

- **Installation Guide:** [INSTALLATION.md](INSTALLATION.md)
- **Architecture Details:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Project Summary:** [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md)
- **API Documentation:** Run `php artisan scribe:generate` then visit `/docs`

## Tech Stack

- **Framework:** Laravel 11
- **PHP:** 8.3+
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum
- **Authorization:** Spatie Laravel Permission
- **API Docs:** Scribe

## Filipino Context Features

 Filipino employee names (Juan dela Cruz, Maria Santos, etc.)
 DepEd-specific positions (Teacher I-III, Master Teacher, Head Teacher VI, Principal IV)
 Philippine government ID fields (TIN, GSIS, PhilHealth, Pag-IBIG)
 DepEd fund sources (MOOE, SEF, DepEd Central)
 Filipino inventory items (Epson L3110 Printer, Bond Paper Sub 20)
 Philippine address format (Brgy., City, Province, ZIP)

## License

php artisan migrate --path=database/migrations/2025_12_12_000001_remove_supplier_details_from_purchase_orders_table.php

php artisan migrate:fresh --seed

Need to re-run migration & seeder