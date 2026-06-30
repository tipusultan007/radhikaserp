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

        $items = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->with(['batch.product', 'productVariant'])
            ->get();

        $productData = [];
        foreach ($items as $item) {
            $productId = $item->batch ? $item->batch->product_id : 'unknown';
            $key = $productId . '_' . ($item->product_variant_id ?? 'none');
            
            if (!isset($productData[$key])) {
                $productData[$key] = [
                    'product_name' => ($item->batch && $item->batch->product) ? $item->batch->product->name : 'N/A',
                    'variant_name' => $item->productVariant ? $item->productVariant->name : 'N/A',
                    'qty_sold' => 0,
                    'revenue' => 0
                ];
            }
            
            $productData[$key]['qty_sold'] += $item->qty;
            $productData[$key]['revenue'] += $item->subtotal;
        }

        // Sort by revenue descending
        usort($productData, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        return view('reports.sales.products', compact('productData', 'startDate', 'endDate'));
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
