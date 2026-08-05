<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['product', 'warehouse', 'purchase'])->latest('id');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('batch_no', 'like', "%{$search}%")
                  ->orWhereHas('product', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('warehouse', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }

        $batches = $query->paginate(20);
        return view('batches.index', compact('batches'));
    }

    public function show(Batch $batch)
    {
        return view('batches.show', compact('batch'));
    }

    public function edit(Batch $batch)
    {
        return view('batches.edit', compact('batch'));
    }

    public function update(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'batch_no' => 'required|string|max:255',
            'cost_per_unit' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        $batch->update($validated);

        return redirect()->route('batches.index')->with('success', 'Batch updated successfully.');
    }

    public function destroy(Batch $batch)
    {
        try {
            if ($batch->qty_out > 0) {
                return back()->with('error', 'Cannot delete batch because it has already been used in sales/transfers.');
            }
            $batch->delete();
            return redirect()->route('batches.index')->with('success', 'Batch deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete batch: ' . $e->getMessage());
        }
    }
}
