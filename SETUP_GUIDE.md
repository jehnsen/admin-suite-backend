# AdminSuite - Complete Setup Guide

This guide will walk you through setting up the AdminSuite backend API from scratch.

## Prerequisites Checklist

Before starting, ensure you have:
- ✅ PHP 8.3 or higher installed
- ✅ Composer 2.x or higher installed
- ✅ MySQL 8.0 or higher installed (XAMPP includes this)
- ✅ Git installed (optional, for version control)

### Verify Prerequisites

```bash
# Check PHP version (must be 8.3+)
php -v

# Check Composer version (must be 2.x+)
composer -V

# Check MySQL version
mysql --version
```

---

## Step-by-Step Installation

### Step 1: Navigate to Project Directory

```bash
cd d:\xampp\apache\bin\ao-suite-backend
```

### Step 2: Install Laravel 11

```bash
# Install Laravel 11 in the current directory
composer create-project laravel/laravel . "11.*" --prefer-dist

# This will take a few minutes...
```

### Step 3: Install Required Packages

```bash
# Install Laravel Sanctum for API authentication
composer require laravel/sanctum

# Install Spatie Laravel Permission for RBAC
composer require spatie/laravel-permission

# Install Scribe for API documentation (dev dependency)
composer require --dev knuckleswtf/scribe
```

### Step 4: Copy Environment File

The `.env` file is already created. Just make sure it exists:

```bash
# On Windows
dir .env

# On Linux/Mac
ls -la .env
```

If not, copy from `.env.example`:
```bash
copy .env.example .env
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

This will update the `APP_KEY` in your `.env` file.

### Step 6: Create MySQL Database

**Option A: Using phpMyAdmin (Recommended for XAMPP users)**

1. Start XAMPP
2. Start Apache and MySQL services
3. Open browser: `http://localhost/phpmyadmin`
4. Click "New" in the left sidebar
5. Database name: `adminsuite_db`
6. Collation: `utf8mb4_unicode_ci`
7. Click "Create"

**Option B: Using MySQL Command Line**

```bash
# Open MySQL command line
mysql -u root -p

# Create database
CREATE DATABASE adminsuite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
exit;
```

### Step 7: Verify Database Configuration

Check your `.env` file has these settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adminsuite_db
DB_USERNAME=root
DB_PASSWORD=
```

**Note:** If your MySQL has a password, update `DB_PASSWORD`.

### Step 8: Publish Vendor Assets

```bash
# Publish Sanctum migrations and config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Spatie Permission migrations and config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Publish Scribe config (optional)
php artisan vendor:publish --tag=scribe-config
```

### Step 9: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Step 10: Copy Project Files

**Important:** Copy all the project files from this directory structure to your Laravel installation:

```
Copy these folders/files:
✓ app/Http/Controllers/Api/
✓ app/Http/Requests/HR/
✓ app/Http/Resources/HR/
✓ app/Services/HR/
✓ app/Repositories/HR/
✓ app/Interfaces/HR/
✓ app/Models/ (all model files)
✓ app/Providers/RepositoryServiceProvider.php
✓ database/migrations/ (all migration files)
✓ database/seeders/ (all seeder files)
✓ routes/api.php
```

### Step 11: Register Repository Service Provider

**Important:** Add the RepositoryServiceProvider to Laravel 11's provider configuration.

Edit `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

### Step 12: Update User Model

Replace `app/Models/User.php` with the provided User model that includes Spatie traits.

### Step 13: Run Migrations

```bash
# Run all migrations (this will create all tables)
php artisan migrate

# If you encounter errors, try fresh migration
php artisan migrate:fresh
```

**Expected Output:**
```
Migration table created successfully.
Migrating: 2019_12_14_000001_create_personal_access_tokens_table
Migrated:  2019_12_14_000001_create_personal_access_tokens_table
Migrating: 2024_01_01_000001_create_employees_table
Migrated:  2024_01_01_000001_create_employees_table
... (more migrations)
```

### Step 14: Seed the Database

```bash
# Seed with all sample data
php artisan db:seed
```

**Expected Output:**
```
Roles and permissions created successfully!
Users created successfully!
Employees created successfully!
Service records created successfully!
Leave requests created successfully!
Inventory items created successfully!
Issuances created successfully!
Budgets created successfully!
Expenses created successfully!
```

**Alternative:** Fresh migration + seeding in one command:
```bash
php artisan migrate:fresh --seed
```

### Step 15: Verify Installation

```bash
# Check if tables were created
php artisan db:show

# Check if seeding worked
php artisan tinker
>>> User::count()
>>> Employee::count()
>>> exit
```

### Step 16: Start Development Server

```bash
php artisan serve
```

**Expected Output:**
```
Starting Laravel development server: http://127.0.0.1:8000
```

Your API is now running at: **http://localhost:8000**

---

## Testing the Installation

### Test 1: Health Check

Open browser or use cURL:
```bash
curl http://localhost:8000/api/health
```

**Expected Response:**
```json
{
    "status": "OK",
    "message": "AdminSuite API is running",
    "timestamp": "2024-12-09T..."
}
```

### Test 2: Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "adminofficer@deped.gov.ph",
    "password": "AdminOfficer123!"
  }'
```

**Expected Response:**
```json
{
    "message": "Login successful",
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
        "id": 3,
        "name": "Jose Protacio Rizal",
        "email": "adminofficer@deped.gov.ph",
        "roles": ["Admin Officer"],
        "permissions": [...]
    }
}
```

### Test 3: Get Employees (Protected Route)

```bash
curl http://localhost:8000/api/employees \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
    "data": [
        {
            "id": 1,
            "employee_number": "EMP-2024-0001",
            "full_name": "Maria Clara Reyes Santos",
            "position": "Principal IV",
            "status": "Active"
        },
        ...
    ],
    "links": {...},
    "meta": {...}
}
```

---

## Optional: Generate API Documentation

```bash
php artisan scribe:generate
```

Access documentation at: **http://localhost:8000/docs**

---

## Common Issues & Solutions

### Issue 1: "No application encryption key has been specified"

**Solution:**
```bash
php artisan key:generate
```

### Issue 2: "SQLSTATE[HY000] [1049] Unknown database 'adminsuite_db'"

**Solution:**
Create the database first in phpMyAdmin or MySQL CLI.

### Issue 3: "Class 'App\Providers\RepositoryServiceProvider' not found"

**Solution:**
Make sure you've copied the `RepositoryServiceProvider.php` file and registered it in `bootstrap/providers.php`.

### Issue 4: Migration errors

**Solution:**
```bash
# Drop all tables and start fresh
php artisan migrate:fresh

# Then seed again
php artisan db:seed
```

### Issue 5: "Spatie\Permission\Exceptions\RoleDoesNotExist"

**Solution:**
Run the seeders again:
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

### Issue 6: CORS errors when accessing from frontend

**Solution:**
Laravel 11 includes CORS support. Configure in `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000'],
```

---

## Post-Installation Checklist

After successful installation, verify:

- ✅ Database `adminsuite_db` exists
- ✅ All migrations ran successfully (17+ tables)
- ✅ Seeders created sample data
- ✅ 4 users exist (Super Admin, School Head, Admin Officer, Teacher)
- ✅ 6 employees exist
- ✅ API health check works
- ✅ Login works and returns token
- ✅ Protected routes require authentication
- ✅ Roles and permissions are working

---

## Next Steps

1. **Test all endpoints** using Postman or Insomnia
2. **Generate API docs** with `php artisan scribe:generate`
3. **Customize permissions** in `RoleAndPermissionSeeder.php`
4. **Add more seeders** for additional test data
5. **Configure email** in `.env` for notifications
6. **Set up queue workers** for background jobs
7. **Write tests** in `tests/Feature/` directory
8. **Deploy to production** server

---

## Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# Run tests
php artisan test

# Fresh start (drops all tables and re-seeds)
php artisan migrate:fresh --seed

# Generate API documentation
php artisan scribe:generate

# Laravel Tinker (interactive shell)
php artisan tinker

# Check database tables
php artisan db:table users
```

---

## Production Deployment

When deploying to production:

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Run `php artisan view:cache`
6. Set up proper queue workers
7. Configure proper database backups
8. Set up SSL/TLS certificates
9. Configure proper logging and monitoring
10. Set strong database passwords

---

## Support

- **Documentation:** See `ARCHITECTURE.md`, `INSTALLATION.md`, `PROJECT_SUMMARY.md`
- **Laravel Docs:** https://laravel.com/docs/11.x
- **Sanctum Docs:** https://laravel.com/docs/11.x/sanctum
- **Spatie Permission:** https://spatie.be/docs/laravel-permission

---

**Congratulations! Your AdminSuite Backend API is now ready for development!** 🎉
