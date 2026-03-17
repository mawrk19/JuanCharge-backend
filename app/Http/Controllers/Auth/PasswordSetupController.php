<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordSetupController extends Controller
{
    public function setPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || $user->status !== 'active') {
                return response()->json(['message' => 'Invalid or unverified email.'], 400);
            }

            $user->password = Hash::make($validated['password']);
            $user->is_first_login = false; // Mark as no longer first login
            $user->save();

            return response()->json(['message' => 'Password has been set successfully. You can now log in.'], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.'], 500);
        }
    }
}
