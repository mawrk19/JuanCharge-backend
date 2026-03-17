<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Mail\WelcomeKioskUser;
use App\Mail\WelcomeRegisteredKioskUser;
use App\Traits\SendsBrevoEmails;

class KioskUserController extends Controller
{
    use SendsBrevoEmails;

    /**
     * Listing of Kiosk Users (Role: Kiosk User).
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = User::where('role_id', Role::KIOSK_USER);

            // If LGU Admin/Staff, maybe filter by those who used their LGU kiosks?
            // For now, list all.

            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch kiosk users: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch users'], 500);
        }
    }

    /**
     * Admin/Staff manual creation of a kiosk user.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:users,email',
                'password' => 'nullable|string|min:6',
                'phone_number' => 'required|string|max:15',
            ]);

            $plainPassword = $validated['password'] ?? \Illuminate\Support\Str::random(10);
            
            $user = User::create([
                'role_id' => Role::KIOSK_USER,
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($plainPassword),
                'status' => 'active',
            ]);

            $mail = new WelcomeKioskUser($user, $plainPassword);
            $this->sendEmailViaBrevo($user->email, 'Welcome to JuanCharge - Your Kiosk Account', $mail->render());

            return response()->json(['success' => true, 'message' => 'Kiosk user created', 'data' => $user], 201);
        } catch (\Exception $e) {
            Log::error('Kiosk user creation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create user'], 500);
        }
    }

    /**
     * Public Self-Registration for Kiosk Users.
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'phone_number' => 'nullable|string|max:15',
            ]);

            $user = User::create([
                'role_id' => Role::KIOSK_USER,
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'points_balance' => 0,
                'points_total' => 0,
                'status' => 'active',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $mail = new WelcomeRegisteredKioskUser($user, $request->password);
            $this->sendEmailViaBrevo($user->email, 'Welcome to JuanCharge! Your Account is Ready', $mail->render());

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token,
                'user_type' => 'kiosk_user'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Kiosk user registration failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Registration failed'], 500);
        }
    }

    public function show($id)
    {
        $user = User::where('role_id', Role::KIOSK_USER)->find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role_id', Role::KIOSK_USER)->find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:64',
            'last_name' => 'sometimes|string|max:64',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone_number' => 'sometimes|string|max:15',
        ]);

        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $user->first_name;
            $lastName = $validated['last_name'] ?? $user->last_name;
            $validated['name'] = trim($firstName . ' ' . $lastName);
        }

        $user->update($validated);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::where('role_id', Role::KIOSK_USER)->find($id);
        if ($user) $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted']);
    }
}
