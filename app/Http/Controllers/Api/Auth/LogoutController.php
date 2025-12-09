<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * Logout endpoint.
 */
class LogoutController extends Controller
{
    /**
     * Logout
     *
     * Revoke the user's current API token.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Logout successful"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
