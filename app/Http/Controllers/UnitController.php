<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::with('parent')->latest()->paginate(15);
        $allUnits = Unit::where('status', true)->get();
        return view('units.index', compact('units', 'allUnits'));
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
        $validated['status'] = $request->has('status');

        Unit::create($validated);
        return redirect()->route('units.index')->with('success', 'Unit created successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'parent_id' => 'nullable|exists:units,id',
            'multiplier' => 'required|numeric|min:0.0001',
            'status' => 'nullable|boolean',
        ]);
        $validated['status'] = $request->has('status');

        $unit->update($validated);
        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
