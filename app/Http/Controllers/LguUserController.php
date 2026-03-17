<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeLguUserMail;
use Illuminate\Support\Facades\URL;
use App\Traits\SendsBrevoEmails;

class LguUserController extends Controller
{
    use SendsBrevoEmails;

    /**
     * Display a listing of LGU users (Admin and Staff roles).
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $query = User::with('lgu', 'role')
                ->whereIn('role_id', [Role::LGU_ADMIN, Role::LGU_STAFF]);

            // If requester is LGU Admin, filter by their LGU
            if ($request->user()->isLguAdmin()) {
                $query->where('lgu_id', $request->user()->lgu_id);
            }

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
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch users'], 500);
        }
    }

    /**
     * Store a newly created LGU user (Admin or Staff).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:users,email',
                'lgu_id' => 'nullable|exists:lgus,id',
                'role_slug' => 'nullable|in:lgu_admin,lgu_staff' // Defaulting to staff if not provided by frontend yet
            ]);

            $roleSlug = $validated['role_slug'] ?? 'lgu_staff';
            $role = Role::where('slug', $roleSlug)->first();
            
            // Authorization Check
            if ($request->user()->isLguAdmin()) {
                $validated['lgu_id'] = $request->user()->lgu_id;
                // LGU Admin can only create LGU Staff (or another Admin in their LGU if permitted)
            }

            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'lgu_id' => $validated['lgu_id'] ?? $request->user()->lgu_id,
                'role_id' => $role->id,
                'status' => 'pending',
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                'is_first_login' => true,
            ]);

            $verificationUrl = URL::temporarySignedRoute(
                'lgu.email.verify', // Ensure this route exists and points to a valid handler
                now()->addHour(),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $mail = new WelcomeLguUserMail($user, $verificationUrl);
            $this->sendEmailViaBrevo($user->email, 'Welcome to JuanCharge - Verify Your Email', $mail->render());

            return response()->json(['success' => true, 'message' => 'User created successfully', 'data' => $user], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function show($id)
    {
        $user = User::with('lgu', 'role')->find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
        
        // Scope check
        if (auth()->user()->isLguAdmin() && $user->lgu_id !== auth()->user()->lgu_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        if (auth()->user()->isLguAdmin() && $user->lgu_id !== auth()->user()->lgu_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:64',
            'last_name' => 'sometimes|string|max:64',
            'email' => 'sometimes|email|unique:users,email,' . $id,
        ]);

        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $user->first_name;
            $lastName = $validated['last_name'] ?? $user->last_name;
            $validated['name'] = trim($firstName . ' ' . $lastName);
        }

        $user->update($validated);
        return response()->json(['success' => true, 'message' => 'User updated', 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        if (auth()->user()->isLguAdmin() && $user->lgu_id !== auth()->user()->lgu_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted']);
    }

    /**
     * Verify email logic for LGU users.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired verification link.');
        }

        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification hash.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->status = 'active'; // Activate user upon verification
            $user->save();
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        // Redirect to setup password page with email filled
        return redirect($frontendUrl . '/setup-password?email=' . urlencode($user->email));
    }

    /**
     * Disable/Enable an LGU user.
     */
    public function disableUser(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        if (auth()->user()->isLguAdmin() && $user->lgu_id !== auth()->user()->lgu_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->status = $user->status === 'active' ? 'disabled' : 'active';
        $user->save();

        return response()->json([
            'success' => true, 
            'message' => 'User status updated to ' . $user->status,
            'data' => $user
        ]);
    }
}
