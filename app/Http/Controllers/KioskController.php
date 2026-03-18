<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use App\Models\KioskRecyclingLog;
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
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            $query = Kiosk::with(['assignedTo', 'lgu', 'collectionSchedule']);

            if ($user && $user->isLguRole()) {
                $query->where('lgu_id', $user->lgu_id);
            }

            $kiosks = $query->get();

            $transformedKiosks = $kiosks->map(function ($kiosk) {
                $data = $kiosk->toArray();

                // Safely add the user name
                $data['assigned_user_name'] = null;
                if ($kiosk->assignedTo) {
                    $data['assigned_user_name'] = $kiosk->assignedTo->name;
                }

                $data['lgu_name'] = $kiosk->lgu ? $kiosk->lgu->name : null;
                $data['collection_schedule_name'] = $kiosk->collectionSchedule ? $kiosk->collectionSchedule->name : null;

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
            if (!$request->user()->isSuperAdmin() && !$request->user()->isLguAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'kiosk_code' => 'required|string|max:50|unique:kiosks',
                'location' => 'required|string|max:255',
                'status' => 'nullable|string|in:active,inactive,maintenance',
                'assigned_to' => 'nullable|exists:users,id',
                'lgu_id' => 'nullable|exists:lgus,id',
                'collection_schedule_id' => 'nullable|exists:collection_schedules,id',
            ]);

            if ($request->user()->isLguAdmin()) {
                $validated['lgu_id'] = $request->user()->lgu_id;
            } elseif (empty($validated['lgu_id'])) {
                return response()->json(['success' => false, 'message' => 'LGU is required.'], 422);
            }

            if (!empty($validated['collection_schedule_id'])) {
                $schedule = \App\Models\CollectionSchedule::find($validated['collection_schedule_id']);
                if (!$schedule || (int) $schedule->lgu_id !== (int) $validated['lgu_id']) {
                    return response()->json(['success' => false, 'message' => 'Selected schedule does not belong to this LGU.'], 422);
                }
            }

            // Set default status if not provided
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }

            $kiosk = Kiosk::create($validated);
            $kiosk->load(['assignedTo', 'lgu', 'collectionSchedule']);

            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            $data['lgu_name'] = $kiosk->lgu ? $kiosk->lgu->name : null;
            $data['collection_schedule_name'] = $kiosk->collectionSchedule ? $kiosk->collectionSchedule->name : null;

            return response()->json([
                'success' => true,
                'message' => 'Kiosk created successfully!',
                'data' => $data
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
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
            // Try matching by primary ID or kiosk_code
            $kiosk = Kiosk::with(['assignedTo', 'lgu', 'collectionSchedule'])
                ->where('id', $id)
                ->orWhere('kiosk_code', $id)
                ->firstOrFail();

            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if ($user && $user->isLguRole() && (int) $kiosk->lgu_id !== (int) $user->lgu_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            $data['lgu_name'] = $kiosk->lgu ? $kiosk->lgu->name : null;
            $data['collection_schedule_name'] = $kiosk->collectionSchedule ? $kiosk->collectionSchedule->name : null;

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

            if (!$request->user()->isSuperAdmin() && !$request->user()->isLguAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($request->user()->isLguAdmin() && (int) $kiosk->lgu_id !== (int) $request->user()->lgu_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'kiosk_code' => 'sometimes|string|max:50|unique:kiosks,kiosk_code,' . $id,
                'location' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:active,inactive,maintenance',
                'assigned_to' => 'nullable|exists:users,id',
                'lgu_id' => 'nullable|exists:lgus,id',
                'collection_schedule_id' => 'nullable|exists:collection_schedules,id',
            ]);

            if ($request->user()->isLguAdmin()) {
                // LGU admin cannot rebind kiosk to another LGU.
                unset($validated['lgu_id']);
            }

            $effectiveLguId = isset($validated['lgu_id'])
                ? (int) $validated['lgu_id']
                : (int) $kiosk->lgu_id;

            if (!empty($validated['collection_schedule_id'])) {
                $schedule = \App\Models\CollectionSchedule::find($validated['collection_schedule_id']);
                if (!$schedule || (int) $schedule->lgu_id !== $effectiveLguId) {
                    return response()->json(['success' => false, 'message' => 'Selected schedule does not belong to this LGU.'], 422);
                }
            }

            $kiosk->update($validated);
            $kiosk->load(['assignedTo', 'lgu', 'collectionSchedule']);

            $data = $kiosk->toArray();
            $data['assigned_user_name'] = $kiosk->assignedTo ? $kiosk->assignedTo->name : null;
            $data['lgu_name'] = $kiosk->lgu ? $kiosk->lgu->name : null;
            $data['collection_schedule_name'] = $kiosk->collectionSchedule ? $kiosk->collectionSchedule->name : null;

            return response()->json([
                'success' => true,
                'message' => 'Kiosk updated successfully!',
                'data' => $data
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
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
    /**
     * Update kiosk heartbeat and status.
     */
    public function heartbeat(Request $request)
    {
        try {
            $validated = $request->validate([
                'kiosk_code' => 'required|string|exists:kiosks,kiosk_code',
                'status' => 'required|string',
                'ports' => 'nullable|array',
                'recycling_stats' => 'nullable|array',
                'timestamp' => 'nullable', // Allow string timestamps (ISO 8601)
            ]);

            $kiosk = Kiosk::where('kiosk_code', $validated['kiosk_code'])->firstOrFail();

            // Fetch pending activations
            $pendingActivations = \App\Models\PortActivation::where('kiosk_id', $kiosk->id)
                ->where('status', 'pending')
                ->get();

            $activationData = $pendingActivations->map(function ($act) {
                return [
                    'id' => $act->id,
                    'port' => $act->port_number,
                    'points' => $act->points,
                    'duration' => $act->duration_seconds,
                    'timestamp' => $act->created_at->timestamp,
                ];
            });

            // Mark as sent
            if ($pendingActivations->count() > 0) {
                \App\Models\PortActivation::whereIn('id', $pendingActivations->pluck('id'))
                    ->update(['status' => 'sent']);
            }

            $kiosk->update([
                'status' => $validated['status'] === 'online' ? 'active' : 'maintenance',
                'last_active' => now(),
                'ip_address' => $request->ip(),
                'details' => ['ports' => $request->ports ?? []],
            ]);

            // Process recycling stats
            if (!empty($validated['recycling_stats'])) {
                $hwTimestamp = now();
                if (isset($validated['timestamp'])) {
                    if (is_numeric($validated['timestamp'])) {
                        $hwTimestamp = \Carbon\Carbon::createFromTimestampMs($validated['timestamp']);
                    } else {
                        try {
                            $hwTimestamp = \Carbon\Carbon::parse($validated['timestamp']);
                        } catch (\Exception $e) {
                            // Keep current time if parse fails
                        }
                    }
                }

                $itemTypeMapping = [
                    'Pet/plastic battles' => 'pet',
                    'Tin/cans' => 'can',
                    'glass_bottle' => 'glass_bottle', // already normalized
                ];

                foreach ($validated['recycling_stats'] as $type => $count) {
                    if ($count > 0) {
                        $normalizedType = $itemTypeMapping[$type] ?? strtolower(str_replace(' ', '_', $type));

                        \App\Models\RecyclingLog::create([
                            'kiosk_id' => $kiosk->id,
                            'item_type' => $normalizedType,
                            'count' => $count,
                            'hardware_timestamp' => $hwTimestamp,
                            'weight_kg' => 0, // Weights are calculated on user sessions
                            'points_earned' => 0,
                            'status' => 'completed'
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat received',
                'pending_activations' => $activationData
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Heartbeat failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time status and port availability for a kiosk.
     */
    public function status($kiosk_code)
    {
        try {
            $kiosk = Kiosk::where('kiosk_code', $kiosk_code)->firstOrFail();

            $isOnline = $kiosk->last_active && $kiosk->last_active->diffInSeconds(now()) <= 30;

            return response()->json([
                'success' => true,
                'data' => [
                    'kiosk_code' => $kiosk->kiosk_code,
                    'location' => $kiosk->location,
                    'status' => $kiosk->status,
                    'is_online' => $isOnline,
                    'last_seen_seconds_ago' => $kiosk->last_active ? $kiosk->last_active->diffInSeconds(now()) : null,
                    'ports' => $kiosk->details['ports'] ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kiosk not found'
            ], 404);
        }
    }
}