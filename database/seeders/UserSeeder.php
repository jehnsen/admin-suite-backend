<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'superadmin@deped.gov.ph',
            'password' => Hash::make('SuperAdmin123!'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('Super Admin');

        // School Head
        $schoolHead = User::create([
            'name' => 'Maria Clara Santos',
            'email' => 'schoolhead@deped.gov.ph',
            'password' => Hash::make('SchoolHead123!'),
            'email_verified_at' => now(),
        ]);
        $schoolHead->assignRole('School Head');

        // Admin Officer
        $adminOfficer = User::create([
            'name' => 'Jose Protacio Rizal',
            'email' => 'adminofficer@deped.gov.ph',
            'password' => Hash::make('AdminOfficer123!'),
            'email_verified_at' => now(),
        ]);
        $adminOfficer->assignRole('Admin Officer');

        // Teacher/Staff
        $teacher = User::create([
            'name' => 'Juan dela Cruz',
            'email' => 'teacher@deped.gov.ph',
            'password' => Hash::make('Teacher123!'),
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('Teacher/Staff');

        $this->command->info('Users created successfully!');
    }
}
