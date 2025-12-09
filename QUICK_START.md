# AdminSuite - Quick Start Guide

Since you already have the `adminsuite_db` database and all project files, follow these steps:

## Step 1: Run the Installation Script

Simply double-click or run:
```bash
install.bat
```

This will:
1. ✅ Install Laravel 11 framework
2. ✅ Install Sanctum, Spatie Permission, and Scribe
3. ✅ Generate application key
4. ✅ Publish vendor configurations
5. ✅ Run migrations
6. ✅ Seed database with Filipino context data

**OR** if you prefer manual steps:

## Step 2: Manual Installation (Alternative)

### 2.1 Install Laravel Core

```bash
composer create-project laravel/laravel temp-install "11.*"
```

### 2.2 Copy Laravel Core Files

Copy these folders from `temp-install` to current directory:
- `bootstrap/`
- `config/`
- `public/`
- `resources/`
- `storage/`
- `vendor/`
- `artisan`

### 2.3 Delete Temporary Installation

```bash
rmdir /S /Q temp-install
```

### 2.4 Update composer.json

The composer.json is already created. Just run:

```bash
composer install
```

### 2.5 Install Required Packages

```bash
composer require laravel/sanctum spatie/laravel-permission
composer require --dev knuckleswtf/scribe
```

### 2.6 Generate Application Key

```bash
php artisan key:generate
```

### 2.7 Publish Vendor Assets

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 2.8 Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2.9 Run Migrations

```bash
php artisan migrate
```

### 2.10 Seed Database

```bash
php artisan db:seed
```

## Step 3: Verify Installation

### Check Database Tables

Your `adminsuite_db` should now have these tables:
- ✅ users
- ✅ roles
- ✅ permissions
- ✅ employees (with 6 Filipino employees)
- ✅ leave_requests
- ✅ service_records
- ✅ inventory_items
- ✅ issuances
- ✅ budgets
- ✅ expenses
- And more...

### Check Seeded Data

```bash
php artisan tinker
```

```php
User::count(); // Should be 4
Employee::count(); // Should be 6
Budget::sum('allocated_amount'); // Should be 2,050,000
exit
```

## Step 4: Start Development Server

```bash
php artisan serve
```

Server will start at: **http://localhost:8000**

## Step 5: Test the API

### Test 1: Health Check

```bash
curl http://localhost:8000/api/health
```

Expected:
```json
{
    "status": "OK",
    "message": "AdminSuite API is running"
}
```

### Test 2: Login

```bash
curl -X POST http://localhost:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"adminofficer@deped.gov.ph\",\"password\":\"AdminOfficer123!\"}"
```

Expected:
```json
{
    "message": "Login successful",
    "token": "1|xxxxxx...",
    "user": {...}
}
```

### Test 3: Get Employees (Protected)

```bash
curl http://localhost:8000/api/employees ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Expected:
```json
{
    "data": [
        {
            "id": 1,
            "employee_number": "EMP-2024-0001",
            "full_name": "Maria Clara Reyes Santos",
            "position": "Principal IV"
        },
        ...
    ]
}
```

## Default Test Credentials

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

## Generate API Documentation

```bash
php artisan scribe:generate
```

Access at: **http://localhost:8000/docs**

## Troubleshooting

### Error: "APP_KEY not set"
```bash
php artisan key:generate
```

### Error: "Database connection failed"
Check your `.env` file:
```env
DB_HOST=localhost
DB_DATABASE=adminsuite_db
DB_USERNAME=root
DB_PASSWORD=password123
```

### Error: "Class RepositoryServiceProvider not found"
Make sure `bootstrap/providers.php` contains:
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

### Migration Errors
```bash
php artisan migrate:fresh --seed
```

## Next Steps

1. ✅ Test all API endpoints with Postman/Insomnia
2. ✅ Review the code in `app/Services/HR/` for business logic
3. ✅ Check `app/Http/Controllers/Api/` for API endpoints
4. ✅ Customize permissions in `database/seeders/RoleAndPermissionSeeder.php`
5. ✅ Generate API documentation: `php artisan scribe:generate`

## Useful Commands

```bash
# Fresh start (WARNING: Drops all tables)
php artisan migrate:fresh --seed

# View all routes
php artisan route:list

# Clear all caches
php artisan optimize:clear

# Run tests
php artisan test

# Interactive shell
php artisan tinker
```

---

**You're all set! Your AdminSuite Backend API is ready!** 🎉
