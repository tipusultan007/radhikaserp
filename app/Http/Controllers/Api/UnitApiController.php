<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitApiController extends Controller
{
    public function index()
    {
        $units = Unit::with('parent')->get();
        return response()->json(['units' => $units]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:units,id',
            'multiplier' => 'required|numeric|min:0.0001',
            'status' => 'nullable|boolean',
        ]);
        $validated['status'] = $request->has('status') ? $request->status : true;

        $unit = Unit::create($validated);
        return response()->json(['message' => 'Unit created', 'unit' => $unit], 201);
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:units,id',
            'multiplier' => 'required|numeric|min:0.0001',
            'status' => 'nullable|boolean',
        ]);
        $validated['status'] = $request->has('status') ? $request->status : true;

        $unit->update($validated);
        return response()->json(['message' => 'Unit updated', 'unit' => $unit]);
    }

    public function destroy($id)
    {
        Unit::destroy($id);
        return response()->json(['message' => 'Unit deleted']);
    }
}
