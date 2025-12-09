# AdminSuite Backend - Installation Guide

## Prerequisites

- PHP >= 8.3
- Composer >= 2.x
- MySQL >= 8.0
- XAMPP/WAMP (if on Windows)

## Step-by-Step Installation

### 1. Install Laravel 11

```bash
# Navigate to the project directory
cd d:\xampp\apache\bin\ao-suite-backend

# Install Laravel (if not already installed)
composer create-project laravel/laravel . "11.*"
```

### 2. Install Required Packages

```bash
# Laravel Sanctum for API authentication
composer require laravel/sanctum

# Spatie Laravel Permission for RBAC
composer require spatie/laravel-permission

# Scribe for API documentation
composer require --dev knuckleswtf/scribe
```

### 3. Publish Vendor Assets

```bash
# Publish Sanctum configuration and migrations
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Spatie Permission configuration and migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Publish Scribe configuration
php artisan vendor:publish --tag=scribe-config
```

### 4. Configure Environment

Edit `.env` file:

```env
APP_NAME="AdminSuite API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adminsuite_db
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8080
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

```sql
-- In MySQL/phpMyAdmin
CREATE DATABASE adminsuite_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run Migrations

```bash
# Run all migrations (including Sanctum and Spatie)
php artisan migrate

# Or with fresh database (drops all tables)
php artisan migrate:fresh
```

### 8. Seed Database with Test Data

```bash
# Seed roles, permissions, and sample data
php artisan db:seed

# Or combine fresh migration + seeding
php artisan migrate:fresh --seed
```

### 9. Register Repository Service Provider

Ensure `RepositoryServiceProvider` is registered in `bootstrap/providers.php` (Laravel 11):

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
```

### 10. Start Development Server

```bash
# Start Laravel development server
php artisan serve

# Or specify port
php artisan serve --port=8000
```

Server will be available at: `http://localhost:8000`

### 11. Generate API Documentation

```bash
# Generate Scribe documentation
php artisan scribe:generate

# Documentation will be available at:
# http://localhost:8000/docs
```

## Testing API Endpoints

### 1. Register a User

```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
    "name": "Juan dela Cruz",
    "email": "juan.delacruz@deped.gov.ph",
    "password": "Password123!",
    "password_confirmation": "Password123!"
}
```

### 2. Login

```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "juan.delacruz@deped.gov.ph",
    "password": "Password123!"
}
```

Response:
```json
{
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": { ... }
}
```

### 3. Access Protected Routes

```bash
GET http://localhost:8000/api/employees
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## Default Credentials (After Seeding)

### Super Admin
- Email: `superadmin@deped.gov.ph`
- Password: `SuperAdmin123!`

### School Head
- Email: `schoolhead@deped.gov.ph`
- Password: `SchoolHead123!`

### Admin Officer
- Email: `adminofficer@deped.gov.ph`
- Password: `AdminOfficer123!`

### Teacher
- Email: `teacher@deped.gov.ph`
- Password: `Teacher123!`

## Roles & Permissions Structure

### Roles
1. **Super Admin** - Full system access
2. **School Head** - School-level management
3. **Admin Officer** - Administrative operations (System Owner)
4. **Teacher/Staff** - Limited access

### Sample Permissions
- `view_employees`
- `create_employees`
- `edit_employees`
- `delete_employees`
- `view_201_file`
- `approve_leave`
- `reject_leave`
- `view_inventory`
- `issue_inventory`
- `view_budget`
- `create_expense`

## Troubleshooting

### Error: "No application encryption key has been specified"
```bash
php artisan key:generate
```

### Error: "SQLSTATE[HY000] [1049] Unknown database"
Create the database first:
```sql
CREATE DATABASE adminsuite_db;
```

### Error: "Class 'App\Providers\RepositoryServiceProvider' not found"
Ensure the provider file exists and is registered in `bootstrap/providers.php`

### Permission Denied Errors
```bash
# On Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### CORS Issues
Install and configure Laravel CORS:
```bash
# Already included in Laravel 11
# Configure in config/cors.php
```

## Project Structure Verification

After installation, verify the structure:
```
app/
├── Http/Controllers/Api/
├── Http/Requests/
├── Http/Resources/
├── Services/
├── Repositories/
├── Interfaces/
├── Models/
└── Providers/RepositoryServiceProvider.php
```

## Next Steps

1. Review the API documentation at `/docs`
2. Test all endpoints using Postman/Insomnia
3. Customize permissions based on your requirements
4. Add additional business logic in Service layer
5. Implement additional modules as needed

## Development Commands

```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run tests
php artisan test

# Database fresh start
php artisan migrate:fresh --seed

# Generate IDE helper (optional)
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:models
```

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Run `php artisan config:cache`
3. Run `php artisan route:cache`
4. Run `php artisan view:cache`
5. Set up proper queue workers for background jobs
6. Configure proper database backups
7. Set up SSL/TLS certificates
8. Configure proper logging and monitoring
