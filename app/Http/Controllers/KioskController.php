<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    /**
     * Display a listing of kiosks.
     */
    public function index()
    {
        $kiosks = Kiosk::with('assignedTo')->paginate(15);
        return response()->json($kiosks);
    }

    /**
     * Store a newly created kiosk.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kiosk_code' => 'required|string|max:50|unique:kiosks',
            'location' => 'required|string|max:255',
            'status' => 'required|string|in:active,inactive,maintenance',
            'serial_number' => 'required|string|unique:kiosks',
            'mac_address' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'software_version' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $kiosk = Kiosk::create($validated);
        return response()->json(['message' => 'Kiosk created successfully!', 'kiosk' => $kiosk]);
    }

    /**
     * Display a specific kiosk.
     */
    public function show($id)
    {
        $kiosk = Kiosk::with('assignedTo')->findOrFail($id);
        return response()->json(['message' => 'Kiosk retrieved successfully!', 'kiosk' => $kiosk]);
    }

    /**
     * Update a kiosk.
     */
    public function update(Request $request, $id)
    {
        $kiosk = Kiosk::findOrFail($id);

        $validated = $request->validate([
            'kiosk_code' => 'sometimes|string|max:50|unique:kiosks,kiosk_code,' . $id,
            'location' => 'sometimes|string|max:255',
            'status' => 'in:active,inactive,maintenance',
            'serial_number' => 'sometimes|string|unique:kiosks,serial_number,' . $id,
            'mac_address' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'software_version' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $kiosk->update($validated);
        $kiosk->load('assignedTo');  // Load the relation
        return response()->json(['message' => 'Kiosk updated successfully!', 'kiosk' => $kiosk]);
    }

    /**
     * Delete a kiosk.
     */
    public function destroy($id)
    {
        $kiosk = Kiosk::findOrFail($id);
        $kiosk->delete();
        return response()->json(['message' => 'Kiosk deleted successfully!']);
    }
}
