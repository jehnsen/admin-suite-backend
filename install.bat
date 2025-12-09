@echo off
echo ========================================
echo AdminSuite Backend - Installation Script
echo ========================================
echo.

echo Step 1: Installing Laravel 11...
composer create-project laravel/laravel temp-install "11.*" --prefer-dist
if errorlevel 1 (
    echo ERROR: Failed to install Laravel
    pause
    exit /b 1
)

echo.
echo Step 2: Copying Laravel core files...
xcopy /E /I /Y temp-install\app\Http\Middleware app\Http\Middleware
xcopy /E /I /Y temp-install\bootstrap bootstrap
xcopy /E /I /Y temp-install\config config
xcopy /E /I /Y temp-install\public public
xcopy /E /I /Y temp-install\resources resources
xcopy /E /I /Y temp-install\storage storage
xcopy /E /I /Y temp-install\tests tests

copy /Y temp-install\artisan artisan
copy /Y temp-install\composer.lock composer.lock

echo.
echo Step 3: Cleaning up temporary installation...
rmdir /S /Q temp-install

echo.
echo Step 4: Installing required packages...
composer require laravel/sanctum spatie/laravel-permission
composer require --dev knuckleswtf/scribe

echo.
echo Step 5: Generating application key...
php artisan key:generate

echo.
echo Step 6: Publishing vendor assets...
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

echo.
echo Step 7: Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo.
echo Step 8: Running migrations...
php artisan migrate

echo.
echo Step 9: Seeding database...
php artisan db:seed

echo.
echo ========================================
echo Installation Complete!
echo ========================================
echo.
echo You can now start the development server with:
echo php artisan serve
echo.
pause
