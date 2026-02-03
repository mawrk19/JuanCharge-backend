<?php

namespace App\Http\Controllers;

use App\Models\Lgu;
use Illuminate\Http\Request;
use Exception;

class LguController extends Controller
{
    /**
     * Display a listing of LGUs.
     */
    public function index()
    {
        try {
            $lgus = Lgu::withCount(['users', 'kiosks'])->get();
            return response()->json([
                'success' => true,
                'data' => $lgus
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch LGUs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created LGU.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'region' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'city_municipality' => 'nullable|string|max:255',
                'barangay' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_number' => 'nullable|string|max:50',
                'status' => 'nullable|string|in:active,inactive,pending',
            ]);

            $lgu = Lgu::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'LGU created successfully!',
                'data' => $lgu
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create LGU: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified LGU.
     */
    public function show($id)
    {
        try {
            $lgu = Lgu::with(['users', 'kiosks'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $lgu
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'LGU not found'
            ], 404);
        }
    }

    /**
     * Update the specified LGU.
     */
    public function update(Request $request, $id)
    {
        try {
            $lgu = Lgu::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'region' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'city_municipality' => 'nullable|string|max:255',
                'barangay' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_number' => 'nullable|string|max:50',
                'status' => 'sometimes|string|in:active,inactive,pending',
            ]);

            $lgu->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'LGU updated successfully!',
                'data' => $lgu
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update LGU: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified LGU.
     */
    public function destroy($id)
    {
        try {
            $lgu = Lgu::findOrFail($id);
            $lgu->delete();
            return response()->json([
                'success' => true,
                'message' => 'LGU deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete LGU: ' . $e->getMessage()
            ], 500);
        }
    }
}
