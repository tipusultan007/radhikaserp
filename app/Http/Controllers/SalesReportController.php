<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function dailySales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $sales = Sale::whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as sale_date'), DB::raw('count(*) as total_orders'), DB::raw('sum(total) as total_revenue'))
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'desc')
            ->get();

        return view('reports.sales.daily', compact('sales', 'startDate', 'endDate'));
    }

    public function monthlySales(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);

        $sales = Sale::whereYear('date', $year)
            ->select(DB::raw('MONTH(date) as sale_month'), DB::raw('count(*) as total_orders'), DB::raw('sum(total) as total_revenue'))
            ->groupBy('sale_month')
            ->orderBy('sale_month', 'asc')
            ->get();

        return view('reports.sales.monthly', compact('sales', 'year'));
    }

    public function productSales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $customerId = $request->input('customer_id');

        $items = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate, $customerId) {
                $q->whereBetween('date', [$startDate, $endDate]);
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            })
            ->with(['batch.product', 'productVariant.product'])
            ->get();

        $productData = [];
        foreach ($items as $item) {
            $productId = 'unknown';
            $productName = 'N/A';
            
            if ($item->productVariant && $item->productVariant->product) {
                $productId = $item->productVariant->product_id;
                $productName = $item->productVariant->product->name;
            } elseif ($item->batch && $item->batch->product) {
                $productId = $item->batch->product_id;
                $productName = $item->batch->product->name;
            }

            if (!isset($productData[$productId])) {
                $productData[$productId] = [
                    'product_name' => $productName,
                    'total_qty' => 0,
                    'total_weight' => 0,
                    'total_revenue' => 0,
                    'variants' => []
                ];
            }

            $variantKey = $item->product_variant_id ?? 'none';
            
            if (!isset($productData[$productId]['variants'][$variantKey])) {
                $productData[$productId]['variants'][$variantKey] = [
                    'variant_name' => $item->productVariant ? $item->productVariant->name : 'N/A',
                    'qty_sold' => 0,
                    'weight' => 0,
                    'revenue' => 0
                ];
            }
            
            $qty = $item->qty;
            
            $weight = $item->total_weight ?? 0;
            if (!$weight) {
                if ($item->productVariant && $item->productVariant->weight) {
                    $weight = $item->productVariant->weight * $qty;
                } elseif ($item->batch && $item->batch->product && $item->batch->product->weight) {
                    $weight = $item->batch->product->weight * $qty;
                }
            }
            
            $rev = $item->total_price ?? ($item->qty * $item->unit_price);
            
            $productData[$productId]['total_qty'] += $qty;
            $productData[$productId]['total_weight'] += $weight;
            $productData[$productId]['total_revenue'] += $rev;
            $productData[$productId]['variants'][$variantKey]['qty_sold'] += $qty;
            $productData[$productId]['variants'][$variantKey]['weight'] += $weight;
            $productData[$productId]['variants'][$variantKey]['revenue'] += $rev;
        }

        // Sort by revenue descending
        usort($productData, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('reports.sales.products', compact('productData', 'startDate', 'endDate', 'customers', 'customerId'));
    }

    public function profitReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $sales = Sale::whereBetween('date', [$startDate, $endDate])
            ->with(['items.batch.product', 'items.productVariant', 'inventoryTransactions'])
            ->orderBy('date', 'desc')
            ->get();

        $profitData = [];
        $totalRevenue = 0;
        $totalCogs = 0;

        foreach ($sales as $sale) {
            $revenue = $sale->total;
            // The COGS for a sale is the total cost of InventoryTransactions (type=sale) linked to it.
            $cogs = $sale->inventoryTransactions->where('type', 'sale')->sum('cost');
            $profit = $revenue - $cogs;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            $profitData[] = [
                'sale' => $sale,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'profit' => $profit,
                'margin' => $margin
            ];

            $totalRevenue += $revenue;
            $totalCogs += $cogs;
        }

        $totalProfit = $totalRevenue - $totalCogs;
        $averageMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return view('reports.sales.profit', compact('profitData', 'startDate', 'endDate', 'totalRevenue', 'totalCogs', 'totalProfit', 'averageMargin'));
    }
}
