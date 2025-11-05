<?php

namespace App\Http\Controllers;

use App\Models\KioskUser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KioskUserController extends Controller
{
    /**
     * Display a listing of kiosk users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = KioskUser::all();
        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created kiosk user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:64|unique:kiosk_users,name',
                'role' => 'required|string|max:32',
                'birth_date' => 'required|date',
                'phone_number' => 'required|string|max:15',
                'email' => 'required|email|max:128|unique:kiosk_users,email',
                'password' => 'nullable|string|min:6', // Optional, will auto-generate if not provided
            ]);

            $user = KioskUser::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kiosk user created successfully',
                'data' => $user,
                'default_password' => $request->has('password') ? null : KioskUser::getPlainDefaultPassword($user->name, $user->birth_date),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified kiosk user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = KioskUser::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kiosk user not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified kiosk user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = KioskUser::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kiosk user not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:64|unique:kiosk_users,name,' . $id,
                'birth_date' => 'sometimes|date',
                'role' => 'sometimes|string|max:32',
                'phone_number' => 'sometimes|string|max:15',
                'email' => 'sometimes|email|max:128|unique:kiosk_users,email,' . $id,
                'password' => 'nullable|string|min:6',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kiosk user updated successfully',
                'data' => $user
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
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
        $user = KioskUser::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kiosk user not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kiosk user deleted successfully'
        ], 200);
    }
}
