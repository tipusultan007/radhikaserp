<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with('manager')->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $users = User::all();
        return view('warehouses.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:warehouses,code|max:255',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        Warehouse::create($validated);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        $users = User::all();
        return view('warehouses.edit', compact('warehouse', 'users'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:warehouses,code,' . $warehouse->id . '|max:255',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
