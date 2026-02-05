# User Management Security Update - Migration Guide

## Overview
This update removes public self-registration and implements admin-controlled user account management for enhanced security.

## What Changed

### 🔒 Security Improvements
1. **Public registration disabled** - No one can self-register anymore
2. **Admin-only user creation** - Only Admin Officer can create user accounts
3. **Email domain validation** - Only `@deped.gov.ph` emails allowed
4. **Role-based restrictions** - Prevents privilege escalation attacks
5. **Password management** - Admin can reset user passwords securely

### 📁 Files Modified
- `routes/api.php` - Public registration endpoint disabled
- `database/seeders/RoleAndPermissionSeeder.php` - Added user management permissions
- `app/Http/Controllers/Api/Admin/UserManagementController.php` - New controller created

## Migration Steps

### Step 1: Backup Database
```bash
# Create a backup before making changes
php artisan backup:run
# OR manually backup your database
mysqldump -u root -p ao_suite_db > backup_$(date +%Y%m%d).sql
```

### Step 2: Reset Permissions and Roles
Since we added new permissions, you need to reseed the roles and permissions table.

**⚠️ WARNING:** This will reset all roles and permissions to default. Any custom permissions will be lost.

```bash
# Option 1: Fresh seed (RECOMMENDED for new installations)
php artisan migrate:fresh --seed

# Option 2: Reseed only roles and permissions (for existing data)
# First, manually delete all permissions and roles:
php artisan tinker
>>> \Spatie\Permission\Models\Permission::truncate();
>>> \Spatie\Permission\Models\Role::truncate();
>>> \DB::table('role_has_permissions')->truncate();
>>> \DB::table('model_has_roles')->truncate();
>>> \DB::table('model_has_permissions')->truncate();
>>> exit

# Then seed
php artisan db:seed --class=RoleAndPermissionSeeder

# Reassign roles to existing users
php artisan tinker
>>> $users = \App\Models\User::all();
>>> foreach ($users as $user) {
>>>     $user->assignRole('Teacher/Staff'); // Or appropriate role
>>> }
>>> exit
```

### Step 3: Assign Admin Officer Account
Make sure you have at least one Admin Officer account to manage users:

```bash
php artisan tinker
>>> $admin = \App\Models\User::where('email', 'your.email@deped.gov.ph')->first();
>>> $admin->syncRoles(['Admin Officer']);
>>> exit
```

### Step 4: Test the Implementation
1. Try to access the old registration endpoint (should not work)
2. Login as Admin Officer
3. Test creating a new user via `/api/users` endpoint
4. Verify the user can login with generated credentials

### Step 5: Clear Application Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## New API Endpoints

### Authentication (Public)
- `POST /api/auth/login` - Login (public)
- ~~`POST /api/auth/register`~~ - **DISABLED**

### User Management (Admin Only)
All endpoints require authentication and appropriate permissions.

#### List Users
```http
GET /api/users
Authorization: Bearer {token}

Response:
{
  "data": [
    {
      "id": 1,
      "name": "Juan dela Cruz",
      "email": "juan@deped.gov.ph",
      "roles": ["Teacher/Staff"],
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": {...}
}
```

#### Create User
```http
POST /api/users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Maria Santos",
  "email": "maria.santos@deped.gov.ph",
  "role": "Teacher/Staff",
  "employee_id": 5
}

Response:
{
  "message": "User created successfully. Please save the temporary password...",
  "user": {
    "id": 10,
    "name": "Maria Santos",
    "email": "maria.santos@deped.gov.ph",
    "roles": ["Teacher/Staff"]
  },
  "temporary_password": "RandomPass123"
}
```

#### Reset User Password
```http
POST /api/users/5/reset-password
Authorization: Bearer {token}
Content-Type: application/json

{}

Response:
{
  "message": "Password reset successfully. Please save the temporary password...",
  "temporary_password": "NewRandomPass456"
}
```

#### Assign Role
```http
POST /api/users/5/assign-role
Authorization: Bearer {token}
Content-Type: application/json

{
  "role": "Admin Officer"
}

Response:
{
  "message": "Role assigned successfully",
  "user": {
    "id": 5,
    "name": "Juan dela Cruz",
    "email": "juan@deped.gov.ph",
    "roles": ["Admin Officer"]
  }
}
```

## User Management Workflow

### Creating a New Teacher/Staff Account
1. Admin Officer logs into the system
2. Navigate to User Management section
3. Click "Create New User"
4. Fill in details:
   - Full name
   - DepEd email address (@deped.gov.ph)
   - Select role (usually "Teacher/Staff")
   - Optional: Link to employee record
5. Submit
6. System generates temporary password
7. Admin Officer securely shares credentials with the new user
8. User logs in and changes password

### Resetting a User's Password
1. Admin Officer navigates to user details
2. Click "Reset Password"
3. System generates new temporary password
4. Admin Officer securely shares new password with user

### Managing User Roles
1. Admin Officer navigates to user details
2. Click "Change Role"
3. Select new role from dropdown
4. Confirm changes

## Permissions Reference

### User Management Permissions
- `view_users` - View user list and details
- `create_users` - Create new user accounts
- `edit_users` - Update user information
- `delete_users` - Delete user accounts
- `reset_user_password` - Reset user passwords
- `manage_user_roles` - Assign/change user roles

### Role Assignments
- **Super Admin**: All permissions (including creating Super Admins)
- **Admin Officer**: All user management permissions (cannot manage Super Admins)
- **School Head**: `view_users` only
- **Teacher/Staff**: No user management access

## Security Notes

### Email Validation
- Only emails ending with `@deped.gov.ph` are accepted
- This prevents unauthorized accounts from being created

### Super Admin Protection
- Only Super Admin can create Super Admin accounts
- Admin Officer cannot modify or delete Super Admin accounts
- This prevents privilege escalation

### Self-Protection
- Users cannot delete their own accounts
- Prevents accidental account deletion

### Password Security
- Minimum 8 characters required
- Auto-generated passwords are random 12-character strings
- Admin should share passwords through secure channels (not email)

## Troubleshooting

### Error: "Role does not exist"
```bash
# Reseed roles and permissions
php artisan db:seed --class=RoleAndPermissionSeeder
```

### Error: "Unauthorized" when creating users
```bash
# Verify your account has the correct permissions
php artisan tinker
>>> $user = \App\Models\User::find(YOUR_ID);
>>> $user->getAllPermissions()->pluck('name');
>>> # Should include 'create_users'
```

### Error: "Email must be @deped.gov.ph"
- Ensure all users use official DepEd email addresses
- For testing, update validation in UserManagementController.php (line 111)

## Rollback Instructions

If you need to rollback these changes:

1. Restore database backup:
```bash
mysql -u root -p ao_suite_db < backup_YYYYMMDD.sql
```

2. Revert code changes:
```bash
git revert HEAD
# OR
git checkout <previous-commit-hash>
```

3. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Support

For questions or issues with this migration:
1. Check the troubleshooting section above
2. Review the MEMORY.md file for implementation details
3. Contact the development team

---

**Migration Date:** 2026-02-05
**Version:** 1.0.0
**Status:** Ready for Production
