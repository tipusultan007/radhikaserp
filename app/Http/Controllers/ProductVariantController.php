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

    public function show(ProductVariant $productVariant)
    {
        $transactions = \App\Models\InventoryTransaction::with(['warehouse', 'batch', 'creator'])
            ->where('product_variant_id', $productVariant->id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);
            
        return view('product_variants.show', compact('productVariant', 'transactions'));
    }

    public function create()
    {
        $products = Product::all();
        $units = \App\Models\Unit::where('status', true)->get();
        return view('product_variants.create', compact('products', 'units'));
    }

    public function generateSku(Request $request)
    {
        $prefix = 'VAR';
        if ($request->has('product_id') && $request->product_id != '') {
            $product = Product::find($request->product_id);
            if ($product) {
                $prefix = $product->sku;
            }
        }
        
        do {
            $sku = $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(4));
        } while (ProductVariant::where('sku', $sku)->exists());
        
        return response()->json(['sku' => $sku]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
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
        $units = \App\Models\Unit::where('status', true)->get();
        return view('product_variants.edit', compact('productVariant', 'products', 'units'));
    }

    public function update(Request $request, ProductVariant $productVariant)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku,' . $productVariant->id . '|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
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
