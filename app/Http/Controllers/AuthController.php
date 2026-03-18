<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Traits\SendsBrevoEmails;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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
        /** @var User|null $user */
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

    /**
     * Validate the current bearer token.
     */
    public function validateToken(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'user' => $user->load('role', 'lgu')
        ]);
    }

    /**
     * Rotate bearer token for authenticated user.
     */
    public function refresh(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Revoke existing tokens then issue a fresh one.
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user->fresh()->load('role', 'lgu'),
            'user_type' => $user->role ? $user->role->slug : 'unknown',
        ]);
    }

    public function logout()
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function updateProfile(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = auth()->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
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

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        if (Hash::check($validated['new_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from current password.'
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->is_first_login = false;
        $user->save();

        // Revoke old tokens for security after password change.
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please login again.'
        ]);
    }

    /**
     * Reset another user's password by ID (admin use).
     */
    public function adminResetPassword(Request $request, $id)
    {
        /** @var User|null $currentUser */
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if (!$currentUser->isSuperAdmin() && !$currentUser->isLguAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:64',
            ],
        ]);

        $targetUser = User::with('role')->find($id);
        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // LGU admins are limited to users in their own LGU and cannot reset super admins.
        if ($currentUser->isLguAdmin()) {
            if ((int) $targetUser->lgu_id !== (int) $currentUser->lgu_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ((int) $targetUser->role_id === Role::SUPER_ADMIN) {
                return response()->json(['success' => false, 'message' => 'Cannot reset super admin password'], 403);
            }
        }

        $targetUser->password = Hash::make($validated['new_password']);
        $targetUser->is_first_login = false;
        $targetUser->save();

        // Revoke active tokens so new password takes effect immediately.
        $targetUser->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
            'data' => [
                'id' => $targetUser->id,
                'email' => $targetUser->email,
                'role_id' => $targetUser->role_id,
            ],
        ]);
    }

    public function requestEmailChangeVerification(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'new_email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        if (strcasecmp($validated['new_email'], $user->email) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'New email must be different from current email.'
            ], 422);
        }

        $token = Str::random(64);
        $cacheKey = 'email_change:' . $token;

        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'new_email' => strtolower($validated['new_email']),
        ], now()->addMinutes(60));

        $verificationUrl = URL::temporarySignedRoute(
            'auth.email.change.verify',
            now()->addMinutes(60),
            ['token' => $token]
        );

        $html = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Confirm Your New Email</h2>
                <p>Hello {$user->name},</p>
                <p>You requested to change your JuanCharge email to <strong>{$validated['new_email']}</strong>.</p>
                <p>Click the button below to confirm this change:</p>
                <p>
                    <a href='{$verificationUrl}' style='display:inline-block;padding:10px 16px;background:#0f766e;color:#fff;text-decoration:none;border-radius:6px;'>
                        Confirm Email Change
                    </a>
                </p>
                <p>This link will expire in 60 minutes.</p>
            </div>
        ";

        $sent = $this->sendEmailViaBrevo($validated['new_email'], 'Confirm your new email - JuanCharge', $html);

        if (!$sent) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent to your new email address.'
        ]);
    }

    public function verifyEmailChange(Request $request, string $token)
    {
        if (!$request->hasValidSignature()) {
            return $this->emailVerificationFrontendRedirect('error', 'Verification failed or expired');
        }

        $cacheKey = 'email_change:' . $token;
        $payload = Cache::get($cacheKey);

        if (!$payload || empty($payload['user_id']) || empty($payload['new_email'])) {
            return $this->emailVerificationFrontendRedirect('error', 'Verification failed or expired');
        }

        $user = User::find($payload['user_id']);
        if (!$user) {
            Cache::forget($cacheKey);
            return $this->emailVerificationFrontendRedirect('error', 'Verification failed or expired');
        }

        if (User::where('email', $payload['new_email'])->where('id', '!=', $user->id)->exists()) {
            Cache::forget($cacheKey);
            return $this->emailVerificationFrontendRedirect('error', 'Verification failed or expired');
        }

        $user->email = $payload['new_email'];
        $user->email_verified_at = now();
        $user->save();

        Cache::forget($cacheKey);

        return $this->emailVerificationFrontendRedirect('success', 'Email verified successfully');
    }

    private function emailVerificationFrontendRedirect(string $status, string $message)
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
        $query = http_build_query([
            'status' => $status,
            'message' => $message,
        ]);

        return redirect()->away($frontendUrl . '/email-verification?' . $query);
    }

    public function sendPhoneVerificationOtp(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $user->id,
        ]);

        $otp = (string) random_int(100000, 999999);
        $phoneNumber = $validated['phone_number'];
        $cacheKey = 'phone_verify:' . $user->id . ':' . $phoneNumber;

        Cache::put($cacheKey, [
            'otp' => $otp,
            'phone_number' => $phoneNumber,
        ], now()->addMinutes(10));

        $smsText = 'Your JuanCharge verification code is ' . $otp . '. Expires in 10 minutes.';
        $smsSent = $this->sendBrevoSms($phoneNumber, $smsText);

        if (!$smsSent) {
            Log::warning('Phone OTP SMS failed to send', ['user_id' => $user->id, 'phone_number' => $phoneNumber]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP to this phone number.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone number.'
        ]);
    }

    public function verifyPhoneOtp(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'otp' => 'required|string|size:6',
        ]);

        $cacheKey = 'phone_verify:' . $user->id . ':' . $validated['phone_number'];
        $payload = Cache::get($cacheKey);

        if (!$payload || !isset($payload['otp']) || $payload['otp'] !== $validated['otp']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.'
            ], 422);
        }

        if (User::where('phone_number', $validated['phone_number'])->where('id', '!=', $user->id)->exists()) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already in use.'
            ], 422);
        }

        $user->phone_number = $validated['phone_number'];
        $user->contact_number_verified_at = now();
        $user->save();

        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
            'user' => $user->fresh()
        ]);
    }
}
