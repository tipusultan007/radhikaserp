<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index()
    {
        $variants = ProductVariant::with('product')->get();
        return view('product_variants.index', compact('variants'));
    }

    public function create()
    {
        $products = Product::all();
        return view('product_variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');
        $validated['price'] = $validated['price'] ?? 0;

        $variant = ProductVariant::create($validated);

        if ($variant->price > 0) {
            \App\Models\PriceHistory::create([
                'product_variant_id' => $variant->id,
                'old_price' => 0,
                'new_price' => $variant->price,
                'changed_by' => auth()->id() ?? 1,
            ]);
        }

        return redirect()->route('product-variants.index')->with('success', 'Product Variant created successfully.');
    }

    public function edit(ProductVariant $productVariant)
    {
        $products = Product::all();
        return view('product_variants.edit', compact('productVariant', 'products'));
    }

    public function update(Request $request, ProductVariant $productVariant)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku,' . $productVariant->id . '|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status');
        $validated['price'] = $validated['price'] ?? 0;

        $oldPrice = $productVariant->price;
        $productVariant->update($validated);

        if ($oldPrice != $productVariant->price) {
            \App\Models\PriceHistory::create([
                'product_variant_id' => $productVariant->id,
                'old_price' => $oldPrice,
                'new_price' => $productVariant->price,
                'changed_by' => auth()->id() ?? 1,
            ]);
        }

        return redirect()->route('product-variants.index')->with('success', 'Product Variant updated successfully.');
    }

    public function destroy(ProductVariant $productVariant)
    {
        $productVariant->delete();
        return redirect()->route('product-variants.index')->with('success', 'Product Variant deleted successfully.');
    }
}
