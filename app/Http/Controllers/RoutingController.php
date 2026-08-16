<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RoutingController extends Controller
{

    public function __construct()
    {
        // $this->
        // middleware('auth')->
        // except('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $totalSales = \App\Models\Sale::sum('total');
        $totalExpenses = \App\Models\Expense::sum('amount');
        $totalPurchases = \App\Models\Purchase::sum('total_cost');
        $totalProducts = \App\Models\Product::count();
        $totalCustomers = \App\Models\Customer::count();
        $totalDue = \App\Models\Sale::sum('due_amount');
        $todaySales = \App\Models\Sale::whereDate('date', \Carbon\Carbon::today())->sum('total');
        $inventoryValue = \App\Models\Batch::selectRaw('SUM(remaining_qty * cost_per_unit) as val')->value('val') ?? 0;
        
        // Cash Balance Approximation
        $cashBalance = \App\Models\JournalEntry::whereHas('account', function($q) {
            $q->where('name', 'Cash');
        })->where('type', 'debit')->sum('amount') 
        - 
        \App\Models\JournalEntry::whereHas('account', function($q) {
            $q->where('name', 'Cash');
        })->where('type', 'credit')->sum('amount');

        // Low Stock Alerts (Details)
        $lowStockBatches = \App\Models\Batch::with(['product', 'productVariant', 'warehouse'])
            ->where('remaining_qty', '<=', 10)
            ->where('remaining_qty', '>', 0)
            ->orderBy('remaining_qty', 'asc')
            ->take(5)
            ->get();
            
        $lowStockAlerts = \App\Models\Batch::where('remaining_qty', '<=', 10)->count();

        // Monthly Financial Comparison
        $thisMonthSales = \App\Models\Sale::whereMonth('date', \Carbon\Carbon::today()->month)
            ->whereYear('date', \Carbon\Carbon::today()->year)->sum('total');
        $lastMonthDate = \Carbon\Carbon::today()->subMonth();
        $lastMonthSales = \App\Models\Sale::whereMonth('date', $lastMonthDate->month)
            ->whereYear('date', $lastMonthDate->year)->sum('total');
        $monthlyGrowthPercent = $lastMonthSales > 0 ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1) : ($thisMonthSales > 0 ? 100 : 0);

        // Recent Activity
        $recentSales = \App\Models\Sale::with('customer')->latest('date')->take(5)->get();
        $recentPurchases = \App\Models\Purchase::with('supplier')->latest('date')->take(5)->get();

        // Top Selling Items
        $topSellingItems = \App\Models\SaleItem::with(['productVariant.product', 'batch.product'])
            ->selectRaw('product_variant_id, batch_id, SUM(qty) as total_qty, SUM(total_price) as total_revenue')
            ->groupBy('product_variant_id', 'batch_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // Expense Categories Breakdown
        $expenseCategories = \App\Models\Expense::with('category')
            ->selectRaw('expense_category_id, SUM(amount) as total_amount')
            ->groupBy('expense_category_id')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

        // Chart.js Data (Last 7 Days)
        $dates = collect();
        $salesData = collect();
        $expensesData = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $dates->push($date->format('M d'));
            
            $salesData->push(\App\Models\Sale::whereDate('date', $date)->sum('total'));
            $expensesData->push(\App\Models\Expense::whereDate('date', $date)->sum('amount'));
        }

        return view('index', compact(
            'totalSales', 
            'totalExpenses', 
            'totalPurchases',
            'totalProducts',
            'totalCustomers',
            'totalDue',
            'todaySales',
            'inventoryValue',
            'cashBalance', 
            'lowStockAlerts', 
            'lowStockBatches', 
            'recentSales', 
            'recentPurchases', 
            'topSellingItems',
            'expenseCategories',
            'thisMonthSales',
            'lastMonthSales',
            'monthlyGrowthPercent',
            'dates', 
            'salesData', 
            'expensesData'
        ));
    }

    /**
     * Display a view based on first route param
     *
     * @return \Illuminate\Http\Response
     */
    public function root(Request $request, $first)
    {

        $mode = $request->query('mode');
        $demo = $request->query('demo');
     
        if ($first == "assets")
            return redirect('home');

        return view($first, ['mode' => $mode, 'demo' => $demo]);
    }

    /**
     * second level route
     */
    public function secondLevel(Request $request, $first, $second)
    {

        $mode = $request->query('mode');
        $demo = $request->query('demo');

        if ($first == "assets")
            return redirect('home');



    return view($first .'.'. $second, ['mode' => $mode, 'demo' => $demo]);
    }

    /**
     * third level route
     */
    public function thirdLevel(Request $request, $first, $second, $third)
    {
        $mode = $request->query('mode');
        $demo = $request->query('demo');

        if ($first == "assets")
            return redirect('home');

        return view($first . '.' . $second . '.' . $third, ['mode' => $mode, 'demo' => $demo]);
    }
}
