<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->paginate(15)->withQueryString();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'total_payable' => 'nullable|numeric|min:0',
        ]);

        $validated['total_payable'] = $validated['total_payable'] ?? 0;

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $apAcc = \App\Models\ChartOfAccount::where('name', 'Accounts Payable')->first();
        
        if ($apAcc) {
            $supplierJournalIds = \App\Models\Journal::where(function($q) use ($supplier) {
                $q->where('reference_type', Supplier::class)->where('reference_id', $supplier->id);
            })->pluck('id');

            $ledgerEntries = \App\Models\JournalEntry::with('journal')
                ->whereIn('journal_id', $supplierJournalIds)
                ->where('account_id', $apAcc->id)
                ->get()
                ->sortBy('id');
        } else {
            $ledgerEntries = collect();
        }

        $paymentMethods = \App\Models\ChartOfAccount::where('is_payment_method', true)->get();

        return view('suppliers.show', compact('supplier', 'ledgerEntries', 'paymentMethods'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'total_payable' => 'nullable|numeric|min:0',
        ]);

        $validated['total_payable'] = $validated['total_payable'] ?? $supplier->total_payable;

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
