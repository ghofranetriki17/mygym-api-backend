<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with(['branch', 'charges', 'categories'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $machines
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'charge_ids' => 'nullable|array',
            'charge_ids.*' => 'exists:charges,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $machine = Machine::create($request->only([
                'branch_id', 'name', 'type', 'description', 'image_url', 'video_url'
            ]));

            // Attach categories if provided
            if ($request->has('category_ids') && !empty($request->category_ids)) {
                $machine->categories()->attach($request->category_ids);
            }

            // Attach charges if provided
            if ($request->has('charge_ids') && !empty($request->charge_ids)) {
                $machine->charges()->attach($request->charge_ids);
            }

            DB::commit();

            // Reload with relationships
            $machine->load(['branch', 'charges', 'categories']);

            return response()->json([
                'success' => true,
                'message' => 'Machine created successfully',
                'data' => $machine
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $machine = Machine::with(['branch', 'charges', 'categories'])->find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $machine
        ]);
    }

    public function update(Request $request, $id)
    {
        $machine = Machine::find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|required|exists:branches,id',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'charge_ids' => 'nullable|array',
            'charge_ids.*' => 'exists:charges,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $machine->update($request->only([
                'branch_id', 'name', 'type', 'description', 'image_url', 'video_url'
            ]));

            // Sync categories if provided
            if ($request->has('category_ids')) {
                $machine->categories()->sync($request->category_ids);
            }

            // Sync charges if provided
            if ($request->has('charge_ids')) {
                $machine->charges()->sync($request->charge_ids);
            }

            DB::commit();

            $machine->load(['branch', 'charges', 'categories']);

            return response()->json([
                'success' => true,
                'message' => 'Machine updated successfully',
                'data' => $machine
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $machine = Machine::find($id);

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Machine not found'
            ], 404);
        }

        try {
            DB::beginTransaction();
            
            // Detach relationships
            $machine->categories()->detach();
            $machine->charges()->detach();
            
            $machine->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Machine deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete machine',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function syncCharges(Request $request, Machine $machine)
    {
        $validator = Validator::make($request->all(), [
            'charge_ids' => 'required|array',
            'charge_ids.*' => 'exists:charges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $machine->charges()->sync($request->charge_ids);

            $machine->load('charges');

            return response()->json([
                'success' => true,
                'message' => 'Charges synchronized successfully',
                'data' => $machine
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync charges',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function attachCharge(Request $request, Machine $machine, Charge $charge)
    {
        try {
            if (!$machine->charges->contains($charge->id)) {
                $machine->charges()->attach($charge->id);
            }

            $machine->load('charges');

            return response()->json([
                'success' => true,
                'message' => 'Charge attached successfully',
                'data' => $machine
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detachCharge(Machine $machine, Charge $charge)
    {
        try {
            $machine->charges()->detach($charge->id);

            $machine->load('charges');

            return response()->json([
                'success' => true,
                'message' => 'Charge detached successfully',
                'data' => $machine
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach charge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByBranch($branchId)
    {
        $machines = Machine::with(['charges', 'categories'])
            ->where('branch_id', $branchId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $machines
        ]);
    }
}