<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoaController extends Controller
{
    public function index()
    {
        $accounts = ChartOfAccount::orderBy('type')->orderBy('name')->get()->groupBy('type');
        return view('accounting.coa.index', compact('accounts'));
    }

    public function create()
    {
        return view('accounting.coa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'opening_balance' => 'required|numeric',
            'is_payment_method' => 'boolean'
        ]);

        $validated['is_payment_method'] = $request->has('is_payment_method');

        DB::beginTransaction();
        try {
            $account = ChartOfAccount::create($validated);

            if ($account->opening_balance > 0) {
                $this->createOpeningBalanceJournal($account);
            }
            DB::commit();
            return redirect()->route('coa.index')->with('success', 'Account created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);
        
        $query = JournalEntry::with('journal')->where('account_id', $id);

        if ($request->filled('start_date')) {
            $query->whereHas('journal', function($q) use ($request) {
                $q->whereDate('date', '>=', $request->start_date);
            });
        }

        if ($request->filled('end_date')) {
            $query->whereHas('journal', function($q) use ($request) {
                $q->whereDate('date', '<=', $request->end_date);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('reference')) {
            $query->whereHas('journal', function($q) use ($request) {
                $q->where('reference_no', 'like', '%' . $request->reference . '%');
            });
        }

        $entries = $query->latest()->paginate(20)->withQueryString();

        return view('accounting.coa.show', compact('account', 'entries'));
    }

    public function edit($id)
    {
        $account = ChartOfAccount::findOrFail($id);
        return view('accounting.coa.edit', compact('account'));
    }

    public function update(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
            'opening_balance' => 'required|numeric',
            'is_payment_method' => 'boolean'
        ]);

        $validated['is_payment_method'] = $request->has('is_payment_method');

        DB::beginTransaction();
        try {
            $oldBalance = (float) $account->opening_balance;
            $account->update($validated);

            $newBalance = (float) $account->opening_balance;

            if ($oldBalance !== $newBalance) {
                $journal = Journal::where('reference_type', ChartOfAccount::class)
                                  ->where('reference_id', $account->id)
                                  ->where('notes', 'Opening Balance')
                                  ->first();
                if ($newBalance > 0) {
                    if ($journal) {
                        // Update existing journal entries
                        $this->updateOpeningBalanceJournal($journal, $account);
                    } else {
                        // Create new journal
                        $this->createOpeningBalanceJournal($account);
                    }
                } else {
                    if ($journal) {
                        // Delete journal if opening balance is now 0
                        $journal->entries()->delete();
                        $journal->delete();
                    }
                }
            }

            DB::commit();
            return redirect()->route('coa.index')->with('success', 'Account updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function createOpeningBalanceJournal($account)
    {
        $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Opening Balance Equity', 'type' => 'equity']);
        
        $journal = Journal::create([
            'journal_no' => 'OB-' . strtoupper(Str::random(6)),
            'date' => date('Y-m-d'),
            'reference_type' => ChartOfAccount::class,
            'reference_id' => $account->id,
            'notes' => 'Opening Balance',
            'created_by' => auth()->id() ?? 1,
        ]);

        $this->updateOpeningBalanceJournal($journal, $account, $equityAcc);
    }

    private function updateOpeningBalanceJournal($journal, $account, $equityAcc = null)
    {
        if (!$equityAcc) {
            $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Opening Balance Equity', 'type' => 'equity']);
        }

        // Remove old entries
        JournalEntry::where('journal_id', $journal->id)->delete();

        if (in_array($account->type, ['asset', 'expense'])) {
            // Debit account, Credit equity
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $account->id, 'type' => 'debit', 'amount' => $account->opening_balance]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'credit', 'amount' => $account->opening_balance]);
        } else {
            // Credit account, Debit equity
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $account->id, 'type' => 'credit', 'amount' => $account->opening_balance]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'debit', 'amount' => $account->opening_balance]);
        }
    }

    public function destroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);
        if ($account->journalEntries()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete account because it has associated journal entries.']);
        }
        $account->delete();
        return redirect()->route('coa.index')->with('success', 'Account deleted successfully.');
    }
}
