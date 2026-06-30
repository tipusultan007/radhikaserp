<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class AccountingApiController extends Controller
{
    /**
     * Get all chart of accounts with their current balances.
     */
    public function chartOfAccounts(Request $request)
    {
        $accounts = ChartOfAccount::all();

        $accountBalances = [];
        foreach ($accounts as $acc) {
            $debits = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credits = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            
            $balance = 0;
            if (in_array($acc->type, ['asset', 'expense'])) {
                $balance = $acc->opening_balance + $debits - $credits;
            } else {
                // liability, equity, income
                $balance = $acc->opening_balance + $credits - $debits;
            }

            $accountBalances[] = [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type,
                'is_payment_method' => $acc->is_payment_method,
                'opening_balance' => $acc->opening_balance,
                'current_balance' => $balance,
                'total_debit' => $debits,
                'total_credit' => $credits,
            ];
        }

        return response()->json(['accounts' => $accountBalances]);
    }

    /**
     * Get cashbook (ledger for Cash account or provided account ID).
     */
    public function cashbook(Request $request)
    {
        $accountId = $request->input('account_id');
        
        if (!$accountId) {
            $cashAccount = ChartOfAccount::where('name', 'Cash')->first();
            if (!$cashAccount) {
                return response()->json(['error' => 'Cash account not found. Please provide account_id.'], 404);
            }
            $accountId = $cashAccount->id;
        }

        $account = ChartOfAccount::findOrFail($accountId);

        $entries = JournalEntry::with(['journal'])
            ->where('account_id', $accountId)
            ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->orderBy('journals.date', 'asc')
            ->orderBy('journals.id', 'asc')
            ->select('journal_entries.*')
            ->get();

        $runningBalance = $account->opening_balance;
        $ledger = [];

        foreach ($entries as $entry) {
            if (in_array($account->type, ['asset', 'expense'])) {
                if ($entry->type === 'debit') {
                    $runningBalance += $entry->amount;
                } else {
                    $runningBalance -= $entry->amount;
                }
            } else {
                if ($entry->type === 'credit') {
                    $runningBalance += $entry->amount;
                } else {
                    $runningBalance -= $entry->amount;
                }
            }

            $ledger[] = [
                'id' => $entry->id,
                'date' => $entry->journal->date,
                'journal_no' => $entry->journal->journal_no,
                'notes' => $entry->journal->notes,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'balance' => $runningBalance,
            ];
        }

        // Sort descending for the view
        $ledger = array_reverse($ledger);

        return response()->json([
            'account' => $account,
            'opening_balance' => $account->opening_balance,
            'current_balance' => $runningBalance,
            'ledger' => $ledger
        ]);
    }

    /**
     * Get Balance Sheet.
     */
    public function balanceSheet(Request $request)
    {
        $accounts = ChartOfAccount::all();

        $assets = [];
        $liabilities = [];
        $equities = [];
        
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        $incomeTotal = 0;
        $expenseTotal = 0;

        foreach ($accounts as $acc) {
            $debits = JournalEntry::where('account_id', $acc->id)->where('type', 'debit')->sum('amount');
            $credits = JournalEntry::where('account_id', $acc->id)->where('type', 'credit')->sum('amount');
            
            if ($acc->type === 'asset') {
                $bal = $acc->opening_balance + $debits - $credits;
                if ($bal != 0) {
                    $assets[] = ['name' => $acc->name, 'balance' => $bal];
                    $totalAssets += $bal;
                }
            } elseif ($acc->type === 'liability') {
                $bal = $acc->opening_balance + $credits - $debits;
                if ($bal != 0) {
                    $liabilities[] = ['name' => $acc->name, 'balance' => $bal];
                    $totalLiabilities += $bal;
                }
            } elseif ($acc->type === 'equity') {
                $bal = $acc->opening_balance + $credits - $debits;
                if ($bal != 0) {
                    $equities[] = ['name' => $acc->name, 'balance' => $bal];
                    $totalEquity += $bal;
                }
            } elseif ($acc->type === 'income') {
                $bal = $acc->opening_balance + $credits - $debits;
                $incomeTotal += $bal;
            } elseif ($acc->type === 'expense') {
                $bal = $acc->opening_balance + $debits - $credits;
                $expenseTotal += $bal;
            }
        }

        $netIncome = $incomeTotal - $expenseTotal;

        // Roll Net Income into Retained Earnings / Equity for Balance Sheet balancing
        $totalEquityWithIncome = $totalEquity + $netIncome;

        return response()->json([
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equities,
            'total_equity' => $totalEquity,
            'net_income' => $netIncome,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquityWithIncome,
        ]);
    }
}
