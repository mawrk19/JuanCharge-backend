<?php

namespace App\Http\Controllers;

use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LguUser;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        if (!$user) {
            $user = KioskUser::where('email', $credentials['email'])->first();
            $userType = 'kiosk_user';
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
            'should_update_profile' => false,
            'prompt_message' => null
        ];

        // Add first login flag for LGU users
        if ($userType === 'lgu_user' && $user->is_first_login) {
            $response['should_update_profile'] = true;
            $response['is_first_login'] = true;
            $response['prompt_message'] = 'Welcome! Please update your profile and change your password for security.';
        }

        // Check if kiosk user has incomplete profile
        if ($userType === 'kiosk_user') {
            $isIncomplete = empty($user->first_name) || empty($user->last_name) || empty($user->contact_number);
            if ($isIncomplete) {
                $response['should_update_profile'] = true;
                $response['prompt_message'] = 'Please complete your profile information.';
            }
        }

        return response()->json($response);
        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Validate if token is still valid
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid or expired',
                'valid' => false
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'valid' => true,
            'user' => $user
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    /**
     * Update authenticated user's profile information
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Determine the table name based on user type
            $tableName = $user->getTable();

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:' . $tableName . ',email,' . $user->id,
                'phone_number' => 'nullable|string|max:15',
            ]);

            // Auto-generate name if first_name and last_name are provided
            if (isset($validated['first_name']) && isset($validated['last_name'])) {
                $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
            }

            // Mark first login as complete for LGU users
            if ($user instanceof \App\Models\LguUser && $user->is_first_login) {
                $validated['is_first_login'] = false;
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change authenticated user's password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 401);
            }

            // Check if new password is same as current
            if (Hash::check($validated['new_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from current password'
                ], 422);
            }

            // Update password
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please login with your new password.',
                'token' => $token
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset link to user's email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email'
            ]);

            $email = $validated['email'];
            $user = null;
            $userType = null;

            // Find user in all three tables
            $user = User::where('email', $email)->first();
            if ($user) {
                $userType = 'admin';
            }

            if (!$user) {
                $user = LguUser::where('email', $email)->first();
                if ($user) {
                    $userType = 'lgu_user';
                }
            }

            if (!$user) {
                $user = KioskUser::where('email', $email)->first();
                if ($user) {
                    $userType = 'kiosk_user';
                }
            }

            // For security, always return success even if email doesn't exist
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'If this email exists, a password reset link has been sent.'
                ], 200);
            }

            // Delete any existing reset tokens for this email
            \App\Models\PasswordResetToken::where('email', $email)->delete();

            // Generate unique token
            $token = bin2hex(random_bytes(32));
            
            // Create reset token record (expires in 1 hour)
            \App\Models\PasswordResetToken::create([
                'email' => $email,
                'token' => hash('sha256', $token),
                'user_type' => $userType,
                'expires_at' => now()->addHour()
            ]);

            // Generate reset link (frontend URL)
            $resetLink = config('app.frontend_url', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

            // Get user name
            $userName = $user->name ?? $user->email;

            // Send email
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PasswordResetMail($resetLink, $userName));

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using token and send new password via email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'token' => 'required|string'
            ]);

            $email = $validated['email'];
            $token = $validated['token'];

            // Find reset token
            $resetToken = \App\Models\PasswordResetToken::where('email', $email)
                ->where('token', hash('sha256', $token))
                ->first();

            // Validate token exists
            if (!$resetToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ], 400);
            }

            // Check if token is expired
            if (now()->gt($resetToken->expires_at)) {
                $resetToken->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new one.'
                ], 400);
            }

            // Find user based on user_type
            $user = null;
            switch ($resetToken->user_type) {
                case 'admin':
                    $user = User::where('email', $email)->first();
                    break;
                case 'lgu_user':
                    $user = LguUser::where('email', $email)->first();
                    break;
                case 'kiosk_user':
                    $user = KioskUser::where('email', $email)->first();
                    break;
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Generate new random password (8-12 characters with mix of letters, numbers)
            $newPassword = $this->generateRandomPassword();

            // Update user password
            $user->password = Hash::make($newPassword);
            $user->save();

            // Revoke all existing tokens
            $user->tokens()->delete();

            // Delete the reset token
            $resetToken->delete();

            // Get user name
            $userName = $user->name ?? null;

            // Send new password via email
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\NewPasswordMail($newPassword, $email, $userName));

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. Check your email for the new password.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a random password
     * 
     * @return string
     */
    private function generateRandomPassword()
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%';
        
        $password = '';
        
        // Ensure at least one of each type
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill the rest randomly (total length 10)
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 10; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
}
