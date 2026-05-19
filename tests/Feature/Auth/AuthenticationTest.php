<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_valid_credentials_return_token(): void
    {
        User::factory()->create(['email' => 'officer@deped.gov.ph']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'officer@deped.gov.ph',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
            ])
            ->assertJsonFragment(['message' => 'Login successful']);
    }

    public function test_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'officer@deped.gov.ph']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'officer@deped.gov.ph',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_non_existent_user_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'ghost@deped.gov.ph',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_deactivated_account_is_blocked(): void
    {
        User::factory()->inactive()->create(['email' => 'inactive@deped.gov.ph']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'inactive@deped.gov.ph',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Your account has been deactivated. Please contact the administrator.']);
    }

    public function test_missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    public function test_missing_password_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'officer@deped.gov.ph',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    public function test_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_logout(): void
    {
        $user  = $this->userWithRole('Admin Officer');
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);
    }

    public function test_unauthenticated_logout_returns_401(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Protected route guard
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_to_protected_route_returns_401(): void
    {
        $this->getJson('/api/employees')
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_reach_protected_route(): void
    {
        $user = $this->userWithRole('Admin Officer');

        $this->actingAs($user)
            ->getJson('/api/employees')
            ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Login clears old tokens
    // -------------------------------------------------------------------------

    public function test_login_invalidates_previous_tokens(): void
    {
        User::factory()->create(['email' => 'officer@deped.gov.ph']);

        // First login
        $first = $this->postJson('/api/auth/login', [
            'email'    => 'officer@deped.gov.ph',
            'password' => 'password',
        ])->json('token');

        // Second login (should revoke first token)
        $this->postJson('/api/auth/login', [
            'email'    => 'officer@deped.gov.ph',
            'password' => 'password',
        ])->assertStatus(200);

        // The first token should no longer work
        $this->withToken($first)
            ->getJson('/api/employees')
            ->assertStatus(401);
    }
}
