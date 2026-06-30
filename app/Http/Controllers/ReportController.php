<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function stockSummary()
    {
        $rawBatches = Batch::whereHas('product', function($q) {
            $q->where('type', 'raw');
        })->with(['product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $packagedBatches = Batch::whereHas('productVariant')->with(['productVariant.product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        return view('reports.stock_summary', compact('rawBatches', 'packagedBatches'));
    }

    public function salesProfit()
    {
        $sales = Sale::with('items')->latest('date')->get();
        // A full implementation would group by day/month and calculate COGS dynamically
        // But since we store total and COGS is via JournalEntries (or we can calculate from items total_cost vs unit_cost?), wait: SaleItem doesn't store COGS directly, InventoryTransaction does.
        // We'll just pass sales to the view and summarize there.
        return view('reports.sales_profit', compact('sales'));
    }

    public function cashbook(Request $request)
    {
        $cashAccs = ChartOfAccount::where('name', 'Cash')->orWhere('name', 'Bank')->pluck('id');
        
        $query = JournalEntry::whereIn('account_id', $cashAccs)->with('journal');

        if ($request->filled('date')) {
            $query->whereHas('journal', function($q) use ($request) {
                $q->whereDate('date', $request->date);
            });
        }

        $entries = $query->latest()->get();
        
        return view('reports.cashbook', compact('entries'));
    }

    public function cashbookPrint(Request $request)
    {
        $cashAccs = ChartOfAccount::where('name', 'Cash')->orWhere('name', 'Bank')->pluck('id');
        
        $query = JournalEntry::whereIn('account_id', $cashAccs)->with('journal');

        if ($request->filled('date')) {
            $query->whereHas('journal', function($q) use ($request) {
                $q->whereDate('date', $request->date);
            });
        }

        $entries = $query->latest()->get();
        
        return view('reports.cashbook_print', compact('entries'));
    }

    public function profitAndLoss()
    {
        $incomeAccounts = ChartOfAccount::where('type', 'income')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $expenseAccounts = ChartOfAccount::where('type', 'expense')->get()->map(function($acc) {
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $acc->balance = $debit - $credit;
            return $acc;
        });

        $incomeTotal = $incomeAccounts->sum('balance');
        $expenseTotal = $expenseAccounts->sum('balance');
        $netProfit = $incomeTotal - $expenseTotal;

        return view('reports.profit_loss', compact('incomeAccounts', 'expenseAccounts', 'incomeTotal', 'expenseTotal', 'netProfit'));
    }

    public function profitAndLossPrint()
    {
        $incomeAccounts = ChartOfAccount::where('type', 'income')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $expenseAccounts = ChartOfAccount::where('type', 'expense')->get()->map(function($acc) {
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $acc->balance = $debit - $credit;
            return $acc;
        });

        $incomeTotal = $incomeAccounts->sum('balance');
        $expenseTotal = $expenseAccounts->sum('balance');
        $netProfit = $incomeTotal - $expenseTotal;

        return view('reports.profit_loss_print', compact('incomeAccounts', 'expenseAccounts', 'incomeTotal', 'expenseTotal', 'netProfit'));
    }

    public function balanceSheet()
    {
        $assetAccounts = ChartOfAccount::where('type', 'asset')->get()->map(function($acc) {
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $acc->balance = $debit - $credit;
            return $acc;
        });

        $liabilityAccounts = ChartOfAccount::where('type', 'liability')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $equityAccounts = ChartOfAccount::where('type', 'equity')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $totalAssets = $assetAccounts->sum('balance');
        $totalLiabilities = $liabilityAccounts->sum('balance');
        $totalEquity = $equityAccounts->sum('balance');

        return view('reports.balance_sheet', compact('assetAccounts', 'liabilityAccounts', 'equityAccounts', 'totalAssets', 'totalLiabilities', 'totalEquity'));
    }

    public function balanceSheetPrint()
    {
        $assetAccounts = ChartOfAccount::where('type', 'asset')->get()->map(function($acc) {
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $acc->balance = $debit - $credit;
            return $acc;
        });

        $liabilityAccounts = ChartOfAccount::where('type', 'liability')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $equityAccounts = ChartOfAccount::where('type', 'equity')->get()->map(function($acc) {
            $credit = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            $debit = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $acc->balance = $credit - $debit;
            return $acc;
        });

        $totalAssets = $assetAccounts->sum('balance');
        $totalLiabilities = $liabilityAccounts->sum('balance');
        $totalEquity = $equityAccounts->sum('balance');

        return view('reports.balance_sheet_print', compact('assetAccounts', 'liabilityAccounts', 'equityAccounts', 'totalAssets', 'totalLiabilities', 'totalEquity'));
    }
}
