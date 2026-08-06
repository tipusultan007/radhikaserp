<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Sale;
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

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        $customers = $query->paginate(15)->withQueryString();
        $districts = \App\Models\District::orderBy('name')->pluck('name');
        return view('customers.index', compact('customers', 'districts'));
    }

    public function searchAjax(Request $request)
    {
        $search = $request->query('q');
        
        if (empty($search)) {
            return response()->json([]);
        }

        $customers = Customer::where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'phone', 'total_due']);
            
        return response()->json($customers);
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
            'customer_type' => 'nullable|in:customer,dealer,special_dealer',
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

        try {
            $admins = \App\Models\User::all();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                'New Customer Added',
                "Customer {$customer->name} has been added.",
                'customer',
                ['customer_id' => $customer->id]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    public function create()
    {
        $districts = \App\Models\District::orderBy('name')->pluck('name');
        return view('customers.create', compact('districts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'customer_type' => 'nullable|in:customer,dealer,special_dealer',
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

            try {
                $admins = \App\Models\User::all();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                    'New Customer Added',
                    "Customer {$customer->name} has been added.",
                    'customer',
                    ['customer_id' => $customer->id]
                ));
            } catch (\Exception $e) {}

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
        $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;
        
        $journals = Journal::with(['entries', 'reference'])
            ->where(function($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\SalePayment::class)->whereIn('reference_id', \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->pluck('id'));
            })
            ->orderBy('date', 'asc')->orderBy('id', 'asc')
            ->get();
            
        $salePayments = \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->get();

        $ledgerEntries = collect();
        $runningBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($journals as $journal) {
            $debit = 0;
            $credit = 0;

            if ($journal->reference_type == Sale::class) {
                $sale = $journal->reference;
                
                // Sale (Debit)
                if ($sale->total >= 0) {
                    $runningBalance += $sale->total;
                    $totalDebit += $sale->total;
                    $ledgerEntries->push((object)[
                        'id' => $journal->id . '_sale',
                        'journal' => $journal,
                        'debit' => $sale->total,
                        'credit' => 0,
                        'running_balance' => $runningBalance,
                    ]);
                }

                // Initial POS Payment (Credit) - fallback for old sales without journals
                $initialPaymentAmount = \App\Models\SalePayment::where('sale_id', $sale->id)
                    ->where(function($q) {
                        $q->whereNull('reference')
                          ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                    })
                    ->sum('amount');

                $hasJournal = $journals->contains(function($j) use ($sale) {
                    return str_contains($j->notes, 'Payment for POS Sale ' . $sale->invoice_no);
                });

                if ($initialPaymentAmount > 0 && !$hasJournal) {
                    $runningBalance -= $initialPaymentAmount;
                    $totalCredit += $initialPaymentAmount;
                    $paymentJournal = clone $journal;
                    $paymentJournal->notes = 'Payment for ' . $sale->invoice_no;
                    
                    $ledgerEntries->push((object)[
                        'id' => $journal->id . '_pay',
                        'journal' => $paymentJournal,
                        'debit' => 0,
                        'credit' => $initialPaymentAmount,
                        'running_balance' => $runningBalance,
                    ]);
                }
                
                continue;
            } else {
                if ($journal->notes == 'Opening Balance') {
                    $debit = $customer->opening_balance;
                } else {
                    $credit = $journal->entries->where('account_id', $arId)->where('type', 'credit')->sum('amount');
                    $debit = $journal->entries->where('account_id', $arId)->where('type', 'debit')->sum('amount');
                }
            }

            $internalTransferAmount = 0;
            // Net out debit and credit so we only show the net change on the running balance
            if ($debit > 0 && $credit > 0) {
                $internalTransferAmount = min($debit, $credit);
                
                if ($debit > $credit) {
                    $debit = $debit - $credit;
                    $credit = 0;
                } else if ($credit > $debit) {
                    $credit = $credit - $debit;
                    $debit = 0;
                } else {
                    $debit = 0;
                    $credit = 0;
                }
            }

            // If it was a pure internal transfer (0 net change), we still want to show it in the ledger so it's not confusingly hidden!
            if ($debit == 0 && $credit == 0 && $internalTransferAmount == 0 && $journal->notes != 'Opening Balance') {
                continue;
            }

            // Append the wallet usage to the notes so the user knows exactly what happened!
            if ($internalTransferAmount > 0) {
                $journal = clone $journal;
                $journal->notes .= " (Wallet Used: ৳" . number_format($internalTransferAmount, 0) . ")";
            }

            $runningBalance += $debit;
            $runningBalance -= $credit;
            
            $totalDebit += $debit;
            $totalCredit += $credit;

            $ledgerEntries->push((object)[
                'id' => $journal->id,
                'journal' => $journal,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ]);
        }

        // The final running balance might differ from DB if older journals were deleted.
        // But we show the running balance of the actual remaining statements.
        $finalRunningBalance = $runningBalance;

        $ledgerEntries = $ledgerEntries->sortByDesc(function($entry) {
            $parts = explode('_', $entry->id);
            $journalId = str_pad($parts[0], 10, '0', STR_PAD_LEFT);
            $subSeq = isset($parts[1]) ? $parts[1] : '0';
            
            $seqMap = [
                'sale' => '1',
                'pay' => '2',
            ];
            $seq = $seqMap[$subSeq] ?? '0';

            return $entry->journal->date . '_' . $journalId . '_' . $seq;
        })->values();

        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();

        return view('customers.show', compact('customer', 'ledgerEntries', 'paymentMethods', 'finalRunningBalance', 'totalDebit', 'totalCredit'));
    }

    public function edit(Customer $customer)
    {
        $districts = \App\Models\District::orderBy('name')->pluck('name');
        return view('customers.edit', compact('customer', 'districts'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'customer_type' => 'nullable|in:customer,dealer,special_dealer',
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

    public function recalculateBalances(Customer $customer)
    {
        $customer->load(['sales']);

        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;
        
        $journals = Journal::with(['entries', 'reference'])
            ->where(function($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\SalePayment::class)->whereIn('reference_id', \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->pluck('id'));
            })
            ->get();

        $calculatedDue = $customer->opening_balance + $customer->sales()->sum('total');
        $advCredit = 0;
        $advDebit = 0;

        foreach ($journals as $journal) {
            if ($journal->notes != 'Opening Balance') {
                $advCredit += $journal->entries->where('account_id', $advId)->where('type', 'credit')->sum('amount');
                $advDebit += $journal->entries->where('account_id', $advId)->where('type', 'debit')->sum('amount');
            }
            
            // Subtract payments recorded via non-sale journals (like Customer or SalePayment)
            if ($journal->reference_type != Sale::class && $journal->notes != 'Opening Balance') {
                $credit = $journal->entries->where('account_id', $arId)->where('type', 'credit')->sum('amount');
                $debit = $journal->entries->where('account_id', $arId)->where('type', 'debit')->sum('amount');
                
                if ($debit > 0 && $credit > 0) {
                    if ($debit > $credit) { $debit = $debit - $credit; $credit = 0; }
                    else if ($credit > $debit) { $credit = $credit - $debit; $debit = 0; }
                    else { $debit = 0; $credit = 0; }
                }

                $calculatedDue -= $credit;
                $calculatedDue += $debit;
            }
        }

        // Subtract fallback POS payments for sales
        $posPayments = 0;
        foreach ($customer->sales as $sale) {
            $initialPaymentAmount = \App\Models\SalePayment::where('sale_id', $sale->id)
                ->where(function($q) {
                    $q->whereNull('reference')
                      ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                })
                ->sum('amount');
                
            $hasJournal = $journals->contains(function($j) use ($sale) {
                return str_contains($j->notes, 'Payment for POS Sale ' . $sale->invoice_no);
            });
            
            if ($initialPaymentAmount > 0 && !$hasJournal) {
                $posPayments += $initialPaymentAmount;
            }
        }
            
        $calculatedDue -= $posPayments;

        $calculatedWallet = $advCredit - $advDebit;

        $customer->total_due = $calculatedDue;
        $customer->wallet_balance = $calculatedWallet;
        $customer->save();

        return redirect()->back()->with('success', 'Customer balances recalculated successfully!');
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
