<?php

namespace Tests;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear stale permission cache left from previous test (RefreshDatabase drops tables)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seed(RoleAndPermissionSeeder::class);

        // Disable rate limiters so login tests don't exhaust the per-minute limit
        RateLimiter::for('login', fn() => Limit::none());
        RateLimiter::for('api', fn() => Limit::none());
    }

    /**
     * Create a user, assign a role, and return the user.
     */
    protected function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }

    /**
     * Create a user with a role and a linked employee record.
     * Returns [$user, $employee].
     */
    protected function userWithEmployee(string $role, array $employeeAttributes = []): array
    {
        $user = $this->userWithRole($role);
        $employee = Employee::factory()->create(
            array_merge(['user_id' => $user->id], $employeeAttributes)
        );
        return [$user, $employee];
    }
}
