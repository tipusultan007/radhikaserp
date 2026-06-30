<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Investment::with(['account', 'creator'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $investments = $query->paginate(15)->appends($request->all());
        $accounts = ChartOfAccount::where('is_payment_method', true)->get();
        return view('investments.index', compact('investments', 'accounts'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::where('is_payment_method', true)->get();
        return view('investments.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:investment,withdraw',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|exists:chart_of_accounts,id',
            'investor_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $validated['created_by'] = auth()->id();
            $investment = Investment::create($validated);

            $this->createJournals($investment);

            DB::commit();
            return redirect()->route('investments.index')->with('success', ucfirst($investment->type) . ' recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording transaction: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Investment $investment)
    {
        $investment->load(['account', 'creator', 'journal.entries.account']);
        return view('investments.show', compact('investment'));
    }

    public function edit(Investment $investment)
    {
        $accounts = ChartOfAccount::where('is_payment_method', true)->get();
        return view('investments.edit', compact('investment', 'accounts'));
    }

    public function update(Request $request, Investment $investment)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:investment,withdraw',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|exists:chart_of_accounts,id',
            'investor_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Reverse old journals
            if ($investment->journal) {
                $investment->journal->entries()->delete();
                $investment->journal()->delete();
            }

            $investment->update($validated);

            // Create new journals
            $this->createJournals($investment);

            DB::commit();
            return redirect()->route('investments.index')->with('success', ucfirst($investment->type) . ' updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating transaction: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Investment $investment)
    {
        try {
            DB::beginTransaction();
            if ($investment->journal) {
                $investment->journal->entries()->delete();
                $investment->journal()->delete();
            }
            $investment->delete();
            DB::commit();
            return redirect()->route('investments.index')->with('success', 'Transaction deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting transaction: ' . $e->getMessage());
        }
    }

    private function createJournals(Investment $investment)
    {
        $journal = Journal::create([
            'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
            'date' => $investment->date,
            'reference_type' => Investment::class,
            'reference_id' => $investment->id,
            'notes' => ucfirst($investment->type) . ' - ' . ($investment->reference ?? 'N/A'),
            'created_by' => auth()->id() ?? 1,
        ]);

        $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Owner\'s Equity / Capital', 'type' => 'equity']);
        $cashAcc = ChartOfAccount::findOrFail($investment->payment_method);

        if ($investment->type === 'investment') {
            // Debit Cash, Credit Equity
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $investment->amount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'credit', 'amount' => $investment->amount]);
        } else {
            // Withdrawal: Debit Equity, Credit Cash
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'debit', 'amount' => $investment->amount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'credit', 'amount' => $investment->amount]);
        }
    }
}
