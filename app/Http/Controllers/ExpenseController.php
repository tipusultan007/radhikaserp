<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'paymentMethod']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }
        
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        $expenses = $query->latest('date')->paginate(15)->withQueryString();
        $categories = ExpenseCategory::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return view('expenses.index', compact('expenses', 'categories', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id() ?? 1;

        try {
            DB::beginTransaction();

            $expense = Expense::create($validated);

            // Accounting integration using the specific category's chart of account
            $expenseAccId = $expense->category->chart_of_account_id;
            if (!$expenseAccId) {
                $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                $expenseAccId = $fallbackAcc->id;
            }
            $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);

            $journal = Journal::create([
                'journal_no' => 'EXP-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'notes' => 'Expense: ' . ($validated['notes'] ?? 'Operational Expense'),
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccId,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $paymentAcc->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return view('expenses.edit', compact('expense', 'categories', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $expense->update($validated);

            $journal = Journal::where('reference_type', Expense::class)
                              ->where('reference_id', $expense->id)
                              ->first();

            if ($journal) {
                // Update journal date/notes
                $journal->update([
                    'date' => $validated['date'],
                    'notes' => 'Expense: ' . ($validated['notes'] ?? 'Operational Expense'),
                ]);

                // Clear old entries
                $journal->entries()->delete();

                // Accounting integration using the specific category's chart of account
                $expenseAccId = $expense->category->chart_of_account_id;
                if (!$expenseAccId) {
                    $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                    $expenseAccId = $fallbackAcc->id;
                }
                $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expenseAccId,
                    'type' => 'debit',
                    'amount' => $validated['amount'],
                ]);

                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $paymentAcc->id,
                    'type' => 'credit',
                    'amount' => $validated['amount'],
                ]);
            }

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Expense $expense)
    {
        try {
            DB::beginTransaction();

            $journal = Journal::where('reference_type', Expense::class)
                              ->where('reference_id', $expense->id)
                              ->first();
            
            if ($journal) {
                $journal->entries()->delete();
                $journal->delete();
            }

            $expense->delete();

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

