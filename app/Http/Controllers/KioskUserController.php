<?php

namespace App\Http\Controllers;
use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\WelcomeKioskUser;
use App\Mail\WelcomeRegisteredKioskUser;

class KioskUserController extends Controller
{
    /**
     * Display a listing of kiosk users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $users = KioskUser::all();
            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:64',
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:kiosk_users,email',
                'password' => 'nullable|string|min:6', // Optional, will auto-generate if not provided
                'contact_number' => 'required|string|max:15',
                'points_balance' => 'nullable|integer|min:0',
                'points_total' => 'nullable|integer|min:0',
                'points_used' => 'nullable|integer|min:0',
                'leaderboard_rank' => 'nullable|string|max:50',
                'total_recyclables_weight' => 'nullable|string|max:255',
                'total_charging_time' => 'nullable|string|max:255',
            ]);

            // Auto-generate name from first_name + last_name
            if (empty($validated['name'])) {
                $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
            }

            // Auto-generate password if not provided (patron + 10 random characters)
            $plainPassword = null;
            if (empty($validated['password'])) {
                $plainPassword = 'patron' . bin2hex(random_bytes(5)); // patron + 10 random chars
                $validated['password'] = $plainPassword;
            } else {
                $plainPassword = $validated['password'];
            }

            // Hash the password before saving
            $validated['password'] = Hash::make($validated['password']);

            $user = KioskUser::create($validated);

            // Send welcome email with credentials
            try {
                Mail::to($user->email)->send(new WelcomeKioskUser($user, $plainPassword));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email to kiosk user: ' . $e->getMessage());
                // Don't fail the user creation if email fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Kiosk user created successfully',
                'data' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Kiosk user creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kiosk user',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = KioskUser::findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:64',
                'first_name' => 'nullable|string|max:64',
                'last_name' => 'nullable|string|max:64',
                'email' => 'nullable|email|max:128|unique:kiosk_users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'contact_number' => 'nullable|string|max:15',
                'points_balance' => 'nullable|integer|min:0',
                'points_total' => 'nullable|integer|min:0',
                'points_used' => 'nullable|integer|min:0',
                'leaderboard_rank' => 'nullable|string|max:50',
                'total_recyclables_weight' => 'nullable|string|max:255',
                'total_charging_time' => 'nullable|string|max:255',
            ]);

            if (isset($validated['name']) && empty($validated['name'])) {
                $validated['name'] = trim(($validated['first_name'] ?? $user->first_name) . ' ' . ($validated['last_name'] ?? $user->last_name));
            }

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = KioskUser::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('User deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }   

    public function disableUser($id)
    {
        try {
            $user = KioskUser::findOrFail($id);
            $user->status = 'inactive';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User disabled successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('User disable failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User disable failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public registration for kiosk users (no authentication required)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:kiosk_users,email',
                'password' => 'required|string|min:6|confirmed', // Password confirmation required
                'contact_number' => 'required|string|max:15',
            ]);

            // Auto-generate name from first_name + last_name
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

            // Store plain password for email
            $plainPassword = $validated['password'];

            // Hash the password before saving
            $validated['password'] = Hash::make($validated['password']);

            // Set default values for new registrations
            $validated['points_balance'] = 0;
            $validated['points_total'] = 0;
            $validated['points_used'] = 0;

            $user = KioskUser::create($validated);

            // Create authentication token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Send welcome email
            try {
                Mail::to($user->email)->send(new WelcomeRegisteredKioskUser($user, $plainPassword));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email to registered kiosk user: ' . $e->getMessage());
                // Don't fail registration if email fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to JuanCharge.',
                'user' => $user,
                'token' => $token,
                'user_type' => 'kiosk_user'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Kiosk user registration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
