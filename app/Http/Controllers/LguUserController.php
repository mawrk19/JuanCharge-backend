<?php

namespace App\Http\Controllers;

use App\Models\LguUser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Mail\WelcomeLguUserMail;
use Illuminate\Support\Facades\URL;

use App\Traits\SendsBrevoEmails;

class LguUserController extends Controller
{
    use SendsBrevoEmails;

    /**
     * Display a listing of LGU users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15); // Default 15 items per page
            $users = LguUser::with('lgu')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created LGU user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:64',
                'last_name' => 'required|string|max:64',
                'email' => 'required|email|max:128|unique:lgu_users,email',
                'lgu_id' => 'nullable|exists:lgus,id',
            ]);

            // Auto-populate lgu_id if not provided and authenticated user has an LGU
            if (empty($validated['lgu_id'])) {
                $authUser = $request->user();
                // Check if user is an LguUser or has an lgu_id attribute
                if ($authUser && isset($authUser->lgu_id)) {
                    $validated['lgu_id'] = $authUser->lgu_id;
                }
            }

            // Auto-generate full name
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
            $validated['is_first_login'] = true;

            // Generate a secure random password for the user
            $plainPassword = \Illuminate\Support\Str::random(10);
            $validated['password'] = $plainPassword; // LguUser model will hash it in creating boot

            // Create user
            $user = LguUser::create($validated);
            $user->load('lgu');

            // Generate a signed URL for email verification
            $verificationUrl = URL::temporarySignedRoute(
                'lgu.email.verify',
                now()->addHour(),
                ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
            );

            // Send welcome email with verification link
            Mail::to($user->email)->send(new WelcomeLguUserMail($user, $verificationUrl));

            return response()->json([
                'success' => true,
                'message' => 'LGU user created successfully. Welcome email sent to ' . $user->email,
                'data' => $user
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('LGU User Validation failed', ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user or send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send welcome email with login credentials
     *
     * @param  LguUser  $user
     * @param  string  $password
     * @return void
     */
    private function sendWelcomeEmail($user, $password)
    {
        Log::info('Queueing welcome email via Laravel Mail for LGU user: ' . $user->email);
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\WelcomeUser($user, $password));
            Log::info('Welcome email successfully queued to: ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email to ' . $user->email . ': ' . $e->getMessage());
            throw new \Exception('Failed to queue welcome email. Check logs for error details.');
        }
    }

    /**
     * Verify the LGU user's email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyEmail(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            return redirect(env('FRONTEND_URL') . '/email-verification-failed');
        }

        $user = LguUser::findOrFail($id);

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL') . '/email-already-verified');
        }

        $user->markEmailAsVerified();

        // Here you can also log the user in, or redirect to a "set password" page
        return redirect(env('FRONTEND_URL') . '/set-password?email=' . urlencode($user->email));
    }

    /**
     * Display the specified LGU user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = LguUser::with('lgu')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGU user not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified LGU user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = LguUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGU user not found'
                ], 404);
            }

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:64',
                'last_name' => 'sometimes|string|max:64',
                'email' => 'sometimes|email|max:128|unique:lgu_users,email,' . $id,
                'lgu_id' => 'sometimes|exists:lgus,id',
            ]);

            // Auto-update full name if first_name or last_name changed
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $firstName = $validated['first_name'] ?? $user->first_name;
                $lastName = $validated['last_name'] ?? $user->last_name;
                $validated['name'] = trim($firstName . ' ' . $lastName);
            }

            $user->update($validated);
            $user->load('lgu');

            return response()->json([
                'success' => true,
                'message' => 'LGU user updated successfully',
                'data' => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified LGU user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = LguUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGU user not found'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'LGU user deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disableUser($id)
    {
        try {
            $user = LguUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGU user not found'
                ], 404);
            }

            $user->status = 'inactive';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'LGU user disabled successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to disable user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to disable user: ' . $e->getMessage()
            ], 500);
        }
    }
}
