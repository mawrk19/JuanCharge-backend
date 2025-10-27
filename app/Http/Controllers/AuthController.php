<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LguUser;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Try to find user in admin users table first
        $user = User::where('email', $credentials['email'])->first();
        $userType = 'admin';
        
        // If not found, check LGU users table
        if (!$user) {
            $user = LguUser::where('email', $credentials['email'])->first();
            $userType = 'lgu_user';
        }

        // Validate user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare response
        $response = [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'user_type' => $userType,
        ];

        // Add first login flag for LGU users
        if ($userType === 'lgu_user') {
            $response['is_first_login'] = $user->is_first_login;
        }

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        // Delete current access token
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}