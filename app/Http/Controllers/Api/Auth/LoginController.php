<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * @group Authentication
 *
 * APIs for user authentication using Laravel Sanctum.
 */
class LoginController extends Controller
{
    /**
     * Login
     *
     * Authenticate user and generate API token.
     *
     * @bodyParam email string required User's email address. Example: juan.delacruz@deped.gov.ph
     * @bodyParam password string required User's password. Example: Password123!
     *
     * @response 200 {
     *   "message": "Login successful",
     *   "token": "1|xxxxxxxxxxxxxxxxxxx",
     *   "user": {
     *     "id": 1,
     *     "name": "Juan dela Cruz",
     *     "email": "juan.delacruz@deped.gov.ph"
     *   }
     * }
     * @response 401 {
     *   "message": "Invalid credentials"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
