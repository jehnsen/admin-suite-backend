# AdminSuite Backend Architecture

## Project Overview
Enterprise ERP System for DepEd Administrative Officers - School Management System

**Tech Stack:**
- PHP 8.3 / Laravel 11+
- MySQL 8.0+
- Laravel Sanctum (Authentication)
- Spatie Laravel Permission (Authorization)
- Scribe (API Documentation)

## Architectural Pattern: Service-Repository

### Layer Responsibilities

1. **Controller Layer** (`app/Http/Controllers/Api`)
   - Handle HTTP requests/responses
   - Validate input using FormRequests
   - Format output using API Resources
   - **NO business logic**

2. **Service Layer** (`app/Services`)
   - Business logic implementation
   - Complex calculations
   - Multi-model transactions
   - Data transformation

3. **Repository Interface** (`app/Interfaces`)
   - Define data access contracts
   - Method signatures

4. **Repository Implementation** (`app/Repositories`)
   - Eloquent/Query Builder operations
   - Database queries
   - Data persistence

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Auth/
│   │       │   ├── LoginController.php
│   │       │   ├── RegisterController.php
│   │       │   └── LogoutController.php
│   │       ├── HR/
│   │       │   ├── EmployeeController.php
│   │       │   ├── LeaveRequestController.php
│   │       │   └── ServiceRecordController.php
│   │       ├── Inventory/
│   │       │   ├── InventoryItemController.php
│   │       │   └── IssuanceController.php
│   │       └── Finance/
│   │           ├── BudgetController.php
│   │           └── ExpenseController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   ├── HR/
│   │   │   ├── StoreEmployeeRequest.php
│   │   │   ├── UpdateEmployeeRequest.php
│   │   │   ├── StoreLeaveRequestRequest.php
│   │   │   └── StoreServiceRecordRequest.php
│   │   ├── Inventory/
│   │   │   ├── StoreInventoryItemRequest.php
│   │   │   └── StoreIssuanceRequest.php
│   │   └── Finance/
│   │       ├── StoreBudgetRequest.php
│   │       └── StoreExpenseRequest.php
│   ├── Resources/
│   │   ├── HR/
│   │   │   ├── EmployeeResource.php
│   │   │   ├── LeaveRequestResource.php
│   │   │   └── ServiceRecordResource.php
│   │   ├── Inventory/
│   │   │   ├── InventoryItemResource.php
│   │   │   └── IssuanceResource.php
│   │   └── Finance/
│   │       ├── BudgetResource.php
│   │       └── ExpenseResource.php
│   └── Middleware/
│       └── CheckPermission.php
├── Services/
│   ├── HR/
│   │   ├── EmployeeService.php
│   │   ├── LeaveRequestService.php
│   │   └── ServiceRecordService.php
│   ├── Inventory/
│   │   ├── InventoryItemService.php
│   │   └── IssuanceService.php
│   └── Finance/
│       ├── BudgetService.php
│       └── ExpenseService.php
├── Repositories/
│   ├── HR/
│   │   ├── EmployeeRepository.php
│   │   ├── LeaveRequestRepository.php
│   │   └── ServiceRecordRepository.php
│   ├── Inventory/
│   │   ├── InventoryItemRepository.php
│   │   └── IssuanceRepository.php
│   └── Finance/
│       ├── BudgetRepository.php
│       └── ExpenseRepository.php
├── Interfaces/
│   ├── HR/
│   │   ├── EmployeeRepositoryInterface.php
│   │   ├── LeaveRequestRepositoryInterface.php
│   │   └── ServiceRecordRepositoryInterface.php
│   ├── Inventory/
│   │   ├── InventoryItemRepositoryInterface.php
│   │   └── IssuanceRepositoryInterface.php
│   └── Finance/
│       ├── BudgetRepositoryInterface.php
│       └── ExpenseRepositoryInterface.php
├── Models/
│   ├── User.php
│   ├── Employee.php
│   ├── LeaveRequest.php
│   ├── ServiceRecord.php
│   ├── InventoryItem.php
│   ├── Issuance.php
│   ├── Budget.php
│   └── Expense.php
└── Providers/
    └── RepositoryServiceProvider.php
```

## Database Schema

### Authentication & RBAC
- `users` - System users
- `roles` - User roles (Spatie)
- `permissions` - Granular permissions (Spatie)
- `model_has_roles` - User-Role pivot
- `model_has_permissions` - User-Permission pivot
- `role_has_permissions` - Role-Permission pivot

### HR Management
- `employees` - Employee master data
- `leave_requests` - Leave applications
- `service_records` - Promotion/transfer history

### Inventory Management
- `inventory_items` - Asset master list
- `issuances` - Custodianship tracking

### Financial Management
- `budgets` - SIP/AIP allocations
- `expenses` - Disbursement tracking

## Security Implementation

### Authentication Flow
1. Login → Generate Sanctum Token
2. Store token in client (localStorage/cookie)
3. Include token in `Authorization: Bearer {token}` header

### Authorization Flow
1. Route middleware: `auth:sanctum`
2. Permission middleware: `permission:view_201_file`
3. Role middleware: `role:Admin Officer`

## API Endpoints Structure

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout

GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}

GET    /api/leave-requests
POST   /api/leave-requests
PUT    /api/leave-requests/{id}/approve
PUT    /api/leave-requests/{id}/reject

GET    /api/inventory-items
POST   /api/inventory-items
POST   /api/issuances

GET    /api/budgets
POST   /api/budgets
GET    /api/expenses
POST   /api/expenses
```

## Installation Steps

1. **Install Laravel 11:**
   ```bash
   composer create-project laravel/laravel ao-suite-backend "11.*"
   cd ao-suite-backend
   ```

2. **Install Dependencies:**
   ```bash
   composer require laravel/sanctum
   composer require spatie/laravel-permission
   composer require knuckleswtf/scribe
   ```

3. **Publish Configurations:**
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan vendor:publish --tag=scribe-config
   ```

4. **Configure Environment:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=adminsuite
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

6. **Seed Database:**
   ```bash
   php artisan db:seed
   ```

## Development Workflow

1. Create Migration → Model → Interface → Repository → Service → Controller → FormRequest → Resource
2. Register Repository binding in `RepositoryServiceProvider`
3. Define API routes with middleware
4. Add Scribe annotations for documentation
5. Write tests (Feature/Unit)

## Code Standards

- **Type Hinting:** Strict typing for all method parameters
- **Return Types:** Explicit return type declarations
- **PHPDoc:** Document all public methods
- **PSR-12:** Follow Laravel's coding standards
- **Naming:**
  - Controllers: `{Model}Controller`
  - Services: `{Model}Service`
  - Repositories: `{Model}Repository`
  - Interfaces: `{Model}RepositoryInterface`
