<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransfer;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BalanceTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = BalanceTransfer::with(['fromAccount', 'toAccount', 'creator']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('from_account_id')) {
            $query->where('from_account_id', $request->from_account_id);
        }
        if ($request->filled('to_account_id')) {
            $query->where('to_account_id', $request->to_account_id);
        }

        $transfers = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->orderBy('name')->get();

        return view('accounting.balance_transfers.index', compact('transfers', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'from_account_id' => 'required|exists:chart_of_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'from_account_id.different' => 'Source and destination accounts must be different.',
        ]);

        try {
            DB::beginTransaction();

            $transfer = BalanceTransfer::create([
                'transfer_no' => 'TRF-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'from_account_id' => $validated['from_account_id'],
                'to_account_id' => $validated['to_account_id'],
                'amount' => $validated['amount'],
                'reference' => $validated['reference'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id() ?? 1,
            ]);

            $journal = Journal::create([
                'journal_no' => 'JNL-TRF-' . strtoupper(Str::random(5)),
                'date' => $validated['date'],
                'reference_type' => BalanceTransfer::class,
                'reference_id' => $transfer->id,
                'notes' => 'Balance Transfer: ' . ($validated['notes'] ?? ''),
                'created_by' => auth()->id() ?? 1,
            ]);

            // Credit the source account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $transfer->from_account_id,
                'type' => 'credit',
                'amount' => $transfer->amount,
            ]);

            // Debit the destination account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $transfer->to_account_id,
                'type' => 'debit',
                'amount' => $transfer->amount,
            ]);

            DB::commit();
            return redirect()->route('balance-transfers.index')->with('success', 'Balance transfer recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, BalanceTransfer $balanceTransfer)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'from_account_id' => 'required|exists:chart_of_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $balanceTransfer->update([
                'date' => $validated['date'],
                'from_account_id' => $validated['from_account_id'],
                'to_account_id' => $validated['to_account_id'],
                'amount' => $validated['amount'],
                'reference' => $validated['reference'],
                'notes' => $validated['notes'],
            ]);

            $journal = $balanceTransfer->journal;
            
            if ($journal) {
                $journal->update([
                    'date' => $validated['date'],
                    'notes' => 'Balance Transfer: ' . ($validated['notes'] ?? ''),
                ]);

                // Delete old entries and create new ones to ensure correct reflection
                $journal->entries()->delete();

                // Credit the source account
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $balanceTransfer->from_account_id,
                    'type' => 'credit',
                    'amount' => $balanceTransfer->amount,
                ]);

                // Debit the destination account
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $balanceTransfer->to_account_id,
                    'type' => 'debit',
                    'amount' => $balanceTransfer->amount,
                ]);
            }

            DB::commit();
            return redirect()->route('balance-transfers.index')->with('success', 'Balance transfer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(BalanceTransfer $balanceTransfer)
    {
        try {
            DB::beginTransaction();

            if ($balanceTransfer->journal) {
                $balanceTransfer->journal->entries()->delete();
                $balanceTransfer->journal()->delete();
            }

            $balanceTransfer->delete();

            DB::commit();
            return redirect()->route('balance-transfers.index')->with('success', 'Balance transfer deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
