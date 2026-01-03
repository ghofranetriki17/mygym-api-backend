<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function index()
    {
        $programmes = Programme::with(['user', 'workouts'])->get();
        return response()->json($programmes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'objectif' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration_weeks' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'workouts' => 'array',
            'workouts.*.id' => 'required|exists:workouts,id',
            'workouts.*.order' => 'nullable|integer|min:0',
            'workouts.*.week_day' => 'nullable|integer|min:1|max:7',
        ]);

        $programme = Programme::create($validated);

        // Attach workouts with pivot data
        if (isset($validated['workouts'])) {
            foreach ($validated['workouts'] as $workoutData) {
                $programme->workouts()->attach($workoutData['id'], [
                    'order' => $workoutData['order'] ?? 0,
                    'week_day' => $workoutData['week_day'] ?? null,
                ]);
            }
        }

        return response()->json($programme->load(['user', 'workouts']), 201);
    }

    public function show(Programme $programme)
    {
        return response()->json($programme->load(['user', 'workouts']));
    }

    public function update(Request $request, Programme $programme)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'objectif' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration_weeks' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $programme->update($validated);
        return response()->json($programme->load(['user', 'workouts']));
    }

    public function destroy(Programme $programme)
    {
        $programme->delete();
        return response()->json(['message' => 'Programme deleted successfully']);
    }

    /**
     * Attach or sync workouts on an existing programme.
     */
    public function syncWorkouts(Request $request, $programmeId)
    {
        $validated = $request->validate([
            'workouts' => 'required|array',
            'workouts.*.id' => 'required|exists:workouts,id',
            'workouts.*.order' => 'nullable|integer|min:0',
            'workouts.*.week_day' => 'nullable|integer|min:1|max:7',
        ]);

        // Ensure the programme exists (avoids null programme_id in pivot)
        $programme = Programme::find($programmeId);
        if (!$programme) {
            return response()->json(['error' => 'Programme not found for this route'], 404);
        }

        // Build sync array with pivot attributes; default order to input position when omitted
        $syncData = collect($validated['workouts'])->mapWithKeys(function ($workoutData, $index) {
            return [
                $workoutData['id'] => [
                    'order' => $workoutData['order'] ?? $index,
                    'week_day' => $workoutData['week_day'] ?? null,
                ],
            ];
        })->all();

        $programme->workouts()->sync($syncData);

        return response()->json($programme->load(['user', 'workouts']));
    }

    public function activate(Programme $programme)
    {
        // Deactivate other programmes for this user
        Programme::where('user_id', $programme->user_id)->update(['is_active' => false]);
        
        // Activate this programme
        $programme->update(['is_active' => true]);
        
        return response()->json(['message' => 'Programme activated successfully']);
    }
}
