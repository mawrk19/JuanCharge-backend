<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\SendsBrevoEmails;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use SendsBrevoEmails;

    /**
     * Unified Login for all users
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with('role')->where('email', $credentials['email'])->first();

        // Validate user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if the user's account is pending
        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending. Please check your email and click the verification link to activate it.'
            ], 403);
        }

        // Create Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Prepare response
        $response = [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'user_type' => $user->role ? $user->role->slug : 'unknown',
            'should_update_profile' => false,
            'prompt_message' => null
        ];

        // First login logic
        if ($user->is_first_login) {
            $response['should_update_profile'] = true;
            $response['prompt_message'] = 'Welcome! Please update your profile and change your password for security.';
        }

        // Profile completeness check for kiosk users
        if ($user->isKioskUser()) {
            $isIncomplete = empty($user->first_name) || empty($user->last_name) || empty($user->phone_number);
            if ($isIncomplete) {
                $response['should_update_profile'] = true;
                $response['prompt_message'] = 'Please complete your profile information.';
            }
        }

        return response()->json($response);
    }

    /**
     * Get the authenticated User.
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
            'user' => $user->load('role', 'lgu')
        ]);
    }

    public function logout()
    {
        if (auth()->user()) {
            auth()->user()->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
                'phone_number' => 'nullable|string|max:15',
            ]);

            if (isset($validated['first_name']) && isset($validated['last_name'])) {
                $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
            }

            if ($user->is_first_login) {
                $validated['is_first_login'] = false;
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate(['email' => 'required|email']);
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'If this email exists, a password reset link has been sent.'
                ], 200);
            }

            \App\Models\PasswordResetToken::where('email', $user->email)->delete();
            $token = bin2hex(random_bytes(32));

            \App\Models\PasswordResetToken::create([
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'user_type' => $user->role ? $user->role->slug : 'user',
                'expires_at' => now()->addHour()
            ]);

            $resetLink = config('app.frontend_url', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
            
            $mail = new \App\Mail\PasswordResetMail($resetLink, $user->name);
            $this->sendEmailViaBrevo($user->email, 'Password Reset Request - JuanCharge', $mail->render());

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'token' => 'required|string'
            ]);

            $resetToken = \App\Models\PasswordResetToken::where('email', $validated['email'])
                ->where('token', hash('sha256', $validated['token']))
                ->first();

            if (!$resetToken || now()->gt($resetToken->expires_at)) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 400);
            }

            $user = User::where('email', $validated['email'])->first();
            if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

            $newPassword = $this->generateRandomPassword();
            $user->password = Hash::make($newPassword);
            $user->save();

            $user->tokens()->delete();
            $resetToken->delete();

            $mail = new \App\Mail\NewPasswordMail($newPassword, $user->email, $user->name);
            $this->sendEmailViaBrevo($user->email, 'Your New Password - JuanCharge', $mail->render());

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. Check your email for the new password.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reset password', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateRandomPassword()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        return substr(str_shuffle($chars), 0, 10);
    }
}
