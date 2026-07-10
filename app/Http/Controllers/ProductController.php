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
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'base_unit' => 'required|string|max:50',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');

        do {
            $sku = 'PRD-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (\App\Models\Product::where('sku', $sku)->exists());
        $validated['sku'] = $sku;

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Master Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'base_unit' => 'required|string|max:50',
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
