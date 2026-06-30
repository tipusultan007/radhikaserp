<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function export(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=customers_export_" . date('Y-m-d_H-i-s') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($customers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array('ID', 'Name', 'Phone', 'Email', 'Address', 'Credit Limit', 'Total Due', 'Opening Balance', 'Created At'));

            foreach ($customers as $customer) {
                fputcsv($file, array(
                    $customer->id,
                    $customer->name,
                    $customer->phone,
                    $customer->email ?? '',
                    $customer->address ?? '',
                    $customer->credit_limit,
                    $customer->total_due,
                    $customer->opening_balance,
                    $customer->created_at->format('Y-m-d H:i:s')
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function ajaxStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
        ]);

        $validated['credit_limit'] = 0;
        $validated['opening_balance'] = 0;
        $validated['total_due'] = 0;

        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? 0;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['total_due'] = $validated['opening_balance']; // Initial due is the opening balance

        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create($validated);

            if ($customer->opening_balance > 0) {
                $this->createOpeningBalanceJournal($customer);
            }

            DB::commit();
            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales' => function ($query) {
            $query->orderBy('date', 'desc');
        }]);

        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        
        if ($arAcc) {
            $customerJournalIds = Journal::where(function($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })->pluck('id');

            $ledgerEntries = JournalEntry::with('journal')
                ->whereIn('journal_id', $customerJournalIds)
                ->where('account_id', $arAcc->id)
                ->get()
                ->sortBy('id');
        } else {
            $ledgerEntries = collect();
        }

        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();

        return view('customers.show', compact('customer', 'ledgerEntries', 'paymentMethods'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? $customer->credit_limit;
        $newOpeningBalance = $validated['opening_balance'] ?? 0;

        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            $oldOpeningBalance = (float) $customer->opening_balance;
            
            // Adjust total_due by the difference in opening_balance
            $diff = $newOpeningBalance - $oldOpeningBalance;
            $validated['total_due'] = $customer->total_due + $diff;

            $customer->update($validated);

            if ($oldOpeningBalance !== (float) $newOpeningBalance) {
                $journal = Journal::where('reference_type', Customer::class)
                                  ->where('reference_id', $customer->id)
                                  ->where('notes', 'Opening Balance')
                                  ->first();

                if ($newOpeningBalance > 0) {
                    if ($journal) {
                        $this->updateOpeningBalanceJournal($journal, $customer);
                    } else {
                        $this->createOpeningBalanceJournal($customer);
                    }
                } else {
                    if ($journal) {
                        $journal->entries()->delete();
                        $journal->delete();
                    }
                }
            }

            DB::commit();
            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    private function createOpeningBalanceJournal($customer)
    {
        $journal = Journal::create([
            'journal_no' => 'OB-CUST-' . strtoupper(Str::random(6)),
            'date' => date('Y-m-d'),
            'reference_type' => Customer::class,
            'reference_id' => $customer->id,
            'notes' => 'Opening Balance',
            'created_by' => auth()->id() ?? 1,
        ]);

        $this->updateOpeningBalanceJournal($journal, $customer);
    }

    private function updateOpeningBalanceJournal($journal, $customer)
    {
        $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Opening Balance Equity', 'type' => 'equity']);
        $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

        JournalEntry::where('journal_id', $journal->id)->delete();

        // Customer opening balance means they owe us (Asset/Debit)
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $customer->opening_balance]);
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'credit', 'amount' => $customer->opening_balance]);
    }
}
