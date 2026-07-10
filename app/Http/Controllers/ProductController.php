<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $units = \App\Models\Unit::where('status', true)->get();
        return view('products.create', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'unit_id' => 'required|exists:units,id',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        do {
            $sku = 'PRD-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (\App\Models\Product::where('sku', $sku)->exists());
        $validated['sku'] = $sku;

        $product = Product::create($validated);

        \App\Models\ProductVariant::create([
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku . '-DEF',
            'unit_qty' => 1,
            'unit_id' => $product->unit_id,
            'price' => 0,
            'status' => true,
        ]);

        return redirect()->route('products.index')->with('success', 'Master Product created successfully.');
    }

    public function edit(Product $product)
    {
        $units = \App\Models\Unit::where('status', true)->get();
        return view('products.edit', compact('product', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'unit_id' => 'required|exists:units,id',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Master Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Master Product deleted successfully.');
    }
}
