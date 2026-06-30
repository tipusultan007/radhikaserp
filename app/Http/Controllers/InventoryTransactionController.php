<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'product', 'productVariant', 'batch', 'creator', 'reference']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest('date')->latest('id')->paginate(15)->withQueryString();
        
        $warehouses = Warehouse::all();
        $products = Product::all();
        $types = InventoryTransaction::select('type')->distinct()->pluck('type');

        return view('inventory_transactions.index', compact('transactions', 'warehouses', 'products', 'types'));
    }
}
