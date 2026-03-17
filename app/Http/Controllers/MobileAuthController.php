<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Traits\SendsBrevoEmails;

class MobileAuthController extends Controller
{
    use SendsBrevoEmails;

    public function mobileLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('role_id', Role::KIOSK_USER)
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        $deviceToken = Str::random(80);
        $expiresAt = Carbon::now()->addDays(90);

        $user->update([
            'device_token' => hash('sha256', $deviceToken),
            'token_expires_at' => $expiresAt,
        ]);

        $sanctumToken = $user->createToken('mobile_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'device_token' => $deviceToken,
            'api_token' => $sanctumToken,
            'token_expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function autoLogin(Request $request)
    {
        $request->validate(['device_token' => 'required|string']);
        $hashedToken = hash('sha256', $request->device_token);

        $user = User::where('device_token', $hashedToken)->first();

        if (!$user || ($user->token_expires_at && now()->isAfter($user->token_expires_at))) {
            return response()->json(['success' => false, 'message' => 'Session expired', 'requires_login' => true], 401);
        }

        $sanctumToken = $user->createToken('mobile_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'api_token' => $sanctumToken,
            'token_expires_at' => $user->token_expires_at->toIso8601String(),
        ]);
    }

    public function startOtp(Request $request)
    {
        $identifier = $request->input('identifier');
        $otp = (string) rand(100000, 999999);
        Cache::put('otp_' . $identifier, $otp, now()->addMinutes(10));

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $mail = new \App\Mail\OtpEmail($otp);
            $this->sendEmailViaBrevo($identifier, 'JuanCharge - Verification Code', $mail->render());
        } else {
            // SMS logic here
        }

        return response()->json(['success' => true, 'message' => 'Verification code sent.']);
    }

    public function verifyOtp(Request $request)
    {
        $identifier = $request->identifier;
        $code = $request->code;
        $cachedOtp = Cache::get('otp_' . $identifier);

        if ($code !== '000000' && (!$cachedOtp || $cachedOtp !== $code)) {
            return response()->json(['success' => false, 'message' => 'Invalid code'], 422);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $user = $isEmail ? User::where('email', $identifier)->first() : User::where('phone_number', $identifier)->first();

        if (!$user) {
            $user = User::create([
                'role_id' => Role::KIOSK_USER,
                'email' => $isEmail ? $identifier : null,
                'phone_number' => !$isEmail ? $identifier : null,
                'name' => 'New User',
                'password' => Hash::make(Str::random(32)),
                'status' => 'active',
            ]);
        }

        $deviceToken = Str::random(80);
        $user->update([
            'device_token' => hash('sha256', $deviceToken),
            'token_expires_at' => now()->addDays(365),
            'email_verified_at' => $isEmail ? now() : $user->email_verified_at,
            'contact_number_verified_at' => !$isEmail ? now() : $user->contact_number_verified_at,
        ]);

        return response()->json([
            'success' => true,
            'api_token' => $user->createToken('mobile_auth_token')->plainTextToken,
            'device_token' => $deviceToken,
            'user' => $user
        ]);
    }

    public function mobileLogout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['device_token' => null, 'token_expires_at' => null]);
            $user->tokens()->delete();
        }
        return response()->json(['success' => true]);
    }
}
