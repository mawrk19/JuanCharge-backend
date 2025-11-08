<?php

namespace App\Http\Controllers;

use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MobileAuthController extends Controller
{
    /**
     * Mobile login for patron (kiosk) users
     * Generates a persistent token stored in the database
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mobileLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Find the kiosk user
        $user = KioskUser::where('email', $credentials['email'])->first();

        // Validate user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        // Generate a persistent device token (long-lived)
        $deviceToken = Str::random(80);
        
        // Token expires in 90 days (for auto-login)
        $expiresAt = Carbon::now()->addDays(90);

        // Store the token in the database
        $user->device_token = hash('sha256', $deviceToken);
        $user->token_expires_at = $expiresAt;
        $user->save();

        // Create Sanctum token for API requests (standard auth)
        $sanctumToken = $user->createToken('mobile_auth_token')->plainTextToken;

        // Check if user has incomplete profile
        $isIncomplete = empty($user->first_name) || empty($user->last_name) || empty($user->contact_number);
        
        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'points_balance' => $user->points_balance,
                'points_total' => $user->points_total,
                'points_used' => $user->points_used,
            ],
            'device_token' => $deviceToken, // Plain token to store in mobile app
            'api_token' => $sanctumToken,   // For API authorization headers
            'token_expires_at' => $expiresAt->toIso8601String(),
            'should_update_profile' => $isIncomplete,
            'prompt_message' => $isIncomplete ? 'Please complete your profile information.' : null
        ];

        return response()->json($response);
    }

    /**
     * Auto-login using stored device token
     * Mobile app sends device_token on subsequent launches
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoLogin(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string'
        ]);

        $deviceToken = $request->input('device_token');
        $hashedToken = hash('sha256', $deviceToken);

        // Find user with matching device token
        $user = KioskUser::where('device_token', $hashedToken)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired device token',
                'requires_login' => true
            ], 401);
        }

        // Check if token has expired
        if ($user->token_expires_at && Carbon::now()->isAfter($user->token_expires_at)) {
            // Clear expired token
            $user->device_token = null;
            $user->token_expires_at = null;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'Device token has expired',
                'requires_login' => true
            ], 401);
        }

        // Generate fresh Sanctum token for API requests
        $sanctumToken = $user->createToken('mobile_auth_token')->plainTextToken;

        // Check if user has incomplete profile
        $isIncomplete = empty($user->first_name) || empty($user->last_name) || empty($user->contact_number);

        return response()->json([
            'success' => true,
            'message' => 'Auto-login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'points_balance' => $user->points_balance,
                'points_total' => $user->points_total,
                'points_used' => $user->points_used,
            ],
            'api_token' => $sanctumToken,
            'token_expires_at' => $user->token_expires_at->toIso8601String(),
            'should_update_profile' => $isIncomplete,
            'prompt_message' => $isIncomplete ? 'Please complete your profile information.' : null
        ]);
    }

    /**
     * Mobile logout - clears the stored device token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mobileLogout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Clear the device token (forces re-login next time)
        $user->device_token = null;
        $user->token_expires_at = null;
        $user->save();

        // Revoke all Sanctum tokens for this user
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Refresh device token (extend expiration)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshDeviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string'
        ]);

        $deviceToken = $request->input('device_token');
        $hashedToken = hash('sha256', $deviceToken);

        $user = KioskUser::where('device_token', $hashedToken)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device token',
                'requires_login' => true
            ], 401);
        }

        // Extend token expiration by 90 days
        $expiresAt = Carbon::now()->addDays(90);
        $user->token_expires_at = $expiresAt;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Device token refreshed',
            'token_expires_at' => $expiresAt->toIso8601String()
        ]);
    }
}
