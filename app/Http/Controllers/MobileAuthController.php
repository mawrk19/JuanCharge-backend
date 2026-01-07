<?php

namespace App\Http\Controllers;

use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

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

    /**
     * Start OTP Process
     * Initiates the login/registration process by sending a verification code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The identifier field is required.',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isMobile = preg_match('/^09\d{9}$/', $identifier); // Philippine mobile format

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'success' => false,
                'message' => 'Identifier must be a valid email or mobile number (09xxxxxxxxx).'
            ], 422);
        }

        // Generate a 6-digit OTP
        $otp = (string) rand(100000, 999999);
        
        // For local/dev testing, you can see it in logs
        // Cache it for 10 minutes
        Cache::put('otp_' . $identifier, $otp, now()->addMinutes(10));

        // Send OTP via Brevo
        $sentInfo = null;
        if ($isEmail) {
            $this->sendBrevoEmail($identifier, $otp);
            Log::info("OTP sent to email {$identifier}");
        } else {
            // Convert to international format if needed (PH specific)
            $mobile = $identifier;
            if (str_starts_with($mobile, '0')) {
                $mobile = '63' . substr($mobile, 1);
            }
            $this->sendBrevoSms($mobile, $otp);
            Log::info("OTP sent to mobile {$mobile}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent.'
        ]);
    }

    /**
     * Verify OTP & Login/Create
     * Validates the code and logs the user in.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input.',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $code = $request->input('code');

        // Check Cache
        $cachedOtp = Cache::get('otp_' . $identifier);

        if (!$cachedOtp || $cachedOtp !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.'
            ], 422);
        }

        // Clear OTP after successful verification
        Cache::forget('otp_' . $identifier);

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        // Find or create user
        if ($isEmail) {
            $user = KioskUser::where('email', $identifier)->first();
        } else {
            $user = KioskUser::where('contact_number', $identifier)->first();
        }

        $isNewUser = false;
        if (!$user) {
            $isNewUser = true;
            $user = new KioskUser();
            if ($isEmail) {
                $user->email = $identifier;
            } else {
                $user->contact_number = $identifier;
            }
            // Set basic defaults for new users
            $user->name = 'New User'; 
            $user->first_name = '';
            $user->last_name = '';
            // Generate random password to satisfy DB constraint
            $user->password = Hash::make(Str::random(32));
        }

        // Update verification timestamp
        if ($isEmail) {
            $user->email_verified_at = now();
        } else {
            $user->contact_number_verified_at = now();
        }

        // Generate a persistent device token (long-lived) for auto-login
        $deviceToken = Str::random(80);
        $expiresAt = now()->addDays(365); // "Forever" - set to 1 year

        $user->device_token = hash('sha256', $deviceToken);
        $user->token_expires_at = $expiresAt;
        $user->save();

        // Issue Sanctum Token for immediate API use
        $token = $user->createToken('mobile_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'api_token' => $token,
            'device_token' => $deviceToken,
            'token_expires_at' => $expiresAt->toIso8601String(),
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
            'should_update_profile' => $isNewUser || empty($user->first_name) || empty($user->last_name) || empty($user->contact_number)
        ]);
    }

    /**
     * Send OTP via Brevo Email API
     */
    private function sendBrevoEmail($email, $otp)
    {
        $apiKey = config('services.brevo.key');
        
        if (!$apiKey) {
            Log::error('Brevo API key not configured');
            return;
        }

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => config('app.name', 'JuanCharge'),
                'email' => config('mail.from.address', 'no-reply@juancharge.com')
            ],
            'to' => [
                ['email' => $email]
            ],
            'subject' => 'Your Login Verification Code',
             'htmlContent' => "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2>Verification Code</h2>
                    <p>Your OTP code is:</p>
                    <h1 style='color: #4CAF50; font-size: 32px; letter-spacing: 5px;'>{$otp}</h1>
                    <p>This code will expire in 10 minutes.</p>
                </div>
            "
        ]);

        if (!$response->successful()) {
            Log::error('Brevo Email Error: ' . $response->body());
        }
    }

    /**
     * Send OTP via Brevo SMS API
     */
    private function sendBrevoSms($mobile, $otp)
    {
        $apiKey = config('services.brevo.key');

        if (!$apiKey) {
            Log::error('Brevo API key not configured');
            return;
        }

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('https://api.brevo.com/v3/transactionalSMS/sms', [
            'sender' => 'JuanCharge', // Max 11 alphanumeric chars
            'recipient' => $mobile,
            'content' => "Your JuanCharge verification code is: {$otp}"
        ]);

        if (!$response->successful()) {
            Log::error('Brevo SMS Error: ' . $response->body());
        }
    }
}
