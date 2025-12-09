<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * @group Authentication
 *
 * User registration endpoint.
 */
class RegisterController extends Controller
{
    /**
     * Register
     *
     * Create a new user account.
     *
     * @bodyParam name string required Full name. Example: Juan dela Cruz
     * @bodyParam email string required Email address. Example: juan.delacruz@deped.gov.ph
     * @bodyParam password string required Password (min 8 characters). Example: Password123!
     * @bodyParam password_confirmation string required Password confirmation. Example: Password123!
     *
     * @response 201 {
     *   "message": "Registration successful",
     *   "token": "1|xxxxxxxxxxxxxxxxxxx",
     *   "user": {
     *     "id": 1,
     *     "name": "Juan dela Cruz",
     *     "email": "juan.delacruz@deped.gov.ph"
     *   }
     * }
     * @response 422 {
     *   "message": "Validation error",
     *   "errors": {}
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default role (Teacher/Staff)
        $user->assignRole('Teacher/Staff');

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }
}
