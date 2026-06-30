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

        // Recent Activity
        $recentSales = \App\Models\Sale::with('customer')->latest('date')->take(5)->get();
        $recentImports = \App\Models\Import::with('supplier')->latest('date')->take(5)->get();

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

        return view('index', compact('totalSales', 'totalExpenses', 'cashBalance', 'lowStockAlerts', 'lowStockBatches', 'recentSales', 'recentImports', 'dates', 'salesData', 'expensesData'));
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
