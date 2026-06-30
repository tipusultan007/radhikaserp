<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $query = Journal::with(['creator', 'entries.account']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('journal_no')) {
            $query->where('journal_no', 'like', '%' . $request->journal_no . '%');
        }

        $journals = $query->latest('date')->paginate(15)->withQueryString();
        
        return view('accounting.journals.index', compact('journals'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::orderBy('name')->get();
        return view('accounting.journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'required|string',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.type' => 'required|in:debit,credit',
            'entries.*.amount' => 'required|numeric|min:1',
        ]);

        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($validated['entries'] as $entry) {
            if ($entry['type'] == 'debit') {
                $debitTotal += $entry['amount'];
            } else {
                $creditTotal += $entry['amount'];
            }
        }

        if (abs($debitTotal - $creditTotal) > 0.001) {
            return back()->withErrors(['error' => 'Journal entries must balance. Debit: ' . $debitTotal . ' Credit: ' . $creditTotal])->withInput();
        }

        try {
            DB::beginTransaction();

            $journal = Journal::create([
                'journal_no' => 'JNL-M-' . strtoupper(Str::random(5)),
                'date' => $validated['date'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id() ?? 1,
            ]);

            foreach ($validated['entries'] as $entry) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $entry['account_id'],
                    'type' => $entry['type'],
                    'amount' => $entry['amount'],
                ]);
            }

            DB::commit();
            return redirect()->route('journals.index')->with('success', 'Journal created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Journal $journal)
    {
        $journal->load(['entries.account', 'creator']);
        return view('accounting.journals.show', compact('journal'));
    }
}

