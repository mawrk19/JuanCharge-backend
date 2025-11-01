<?php

namespace App\Http\Controllers;

use App\Models\LguUser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class LguUserController extends Controller
{
    /**
     * Display a listing of LGU users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $users = LguUser::all();
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
                'name' => 'required|string|max:64|unique:lgu_users,name',
                'role' => 'required|string|max:32',
                'phone_number' => 'required|string|max:15',
                'email' => 'required|email|max:128|unique:lgu_users,email',
            ]);

            // Generate default password
            $defaultPassword = 'LGU' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            $validated['password'] = \Hash::make($defaultPassword);
            $validated['is_first_login'] = true;

            $user = LguUser::create($validated);
            
            // Send welcome email with credentials
            $this->sendWelcomeEmail($user, $defaultPassword);
            
            return response()->json([
                'success' => true,
                'message' => 'LGU user created successfully. Welcome email sent to ' . $user->email,
                'data' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User created but failed to send email: ' . $e->getMessage()
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
        $emailBody = "
Hello {$user->name},

Welcome to JuanCharge LGU Portal!

Your account has been successfully created. Below are your login credentials:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
LOGIN CREDENTIALS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Email: {$user->email}
 Password: {$password}
 Role: {$user->role}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

 IMPORTANT SECURITY NOTICE:
• This is a temporary password
• You will be required to change it on first login
• Please keep this information confidential
• Do not share your password with anyone

 Access the portal at: http://localhost:3000/login

For any assistance, please contact support.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
JuanCharge - Powering Your Journey
        ";

        try {
            // Only send email if mail is properly configured
            if (config('mail.mailers.smtp.host')) {
                Mail::raw($emailBody, function($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Welcome to JuanCharge - Your Login Credentials');
                });
            } else {
                // Log the credentials if email is not configured
                Log::info('User created - Email not sent (mail not configured)', [
                    'email' => $user->email,
                    'password' => $password
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email to ' . $user->email . ': ' . $e->getMessage());
            // Don't throw - just log the error so user creation still succeeds
        }
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
            $user = LguUser::find($id);

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
                'name' => 'sometimes|string|max:64|unique:lgu_users,name,' . $id,
                'role' => 'sometimes|string|max:32',
                'phone_number' => 'sometimes|string|max:15',
                'email' => 'sometimes|email|max:128|unique:lgu_users,email,' . $id,
            ]);

            $user->update($validated);

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
}