<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use Illuminate\Http\Request;
use Exception;

class KioskController extends Controller
{
    /**
     * Display a listing of kiosks.
     */
    public function index()
    {
        try {
            $kiosks = Kiosk::with('assignedTo')->get();
            
            $transformedKiosks = $kiosks->map(function($kiosk) {
                $data = $kiosk->toArray();
                
                // Safely add the user name
                $data['assigned_user_name'] = null;
                if ($kiosk->assignedTo) {
                    $data['assigned_user_name'] = $kiosk->assignedTo->name;
                }
                
                return $data;
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedKiosks
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kiosks: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created kiosk.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kiosk_code' => 'required|string|max:50|unique:kiosks',
                'location' => 'required|string|max:255',
                'status' => 'required|string|in:active,inactive,maintenance',
                'serial_number' => 'required|string|unique:kiosks',
                'mac_address' => 'nullable|string',
                'ip_address' => 'nullable|ip',
                'software_version' => 'nullable|string',
                'assigned_to' => 'nullable|exists:lgu_users,id',
                'notes' => 'nullable|string',
            ]);

            $kiosk = Kiosk::create($validated);
            $kiosk->load('assignedTo');
            
            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            
            return response()->json([
                'success' => true,
                'message' => 'Kiosk created successfully!',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create kiosk: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a specific kiosk.
     */
    public function show($id)
    {
        try {
            $kiosk = Kiosk::with('assignedTo')->findOrFail($id);
            
            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch kiosk: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a kiosk.
     */
    public function update(Request $request, $id)
    {
        try {
            $kiosk = Kiosk::findOrFail($id);

            $validated = $request->validate([
                'kiosk_code' => 'sometimes|string|max:50|unique:kiosks,kiosk_code,' . $id,
                'location' => 'sometimes|string|max:255',
                'status' => 'in:active,inactive,maintenance',
                'serial_number' => 'sometimes|string|unique:kiosks,serial_number,' . $id,
                'mac_address' => 'nullable|string',
                'ip_address' => 'nullable|ip',
                'software_version' => 'nullable|string',
                'assigned_to' => 'nullable|exists:lgu_users,id',
                'notes' => 'nullable|string',
            ]);

            $kiosk->update($validated);
            $kiosk->load('assignedTo');
            
            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            
            return response()->json([
                'success' => true,
                'message' => 'Kiosk updated successfully!',
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update kiosk: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a kiosk.
     */
    public function destroy($id)
    {
        try {
            $kiosk = Kiosk::findOrFail($id);
            $kiosk->delete();
            return response()->json([
                'success' => true,
                'message' => 'Kiosk deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete kiosk: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}