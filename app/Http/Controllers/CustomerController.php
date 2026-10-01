<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Sale;
use App\Models\StatementToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
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
            ->orWhere('company', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'phone', 'company', 'total_due']);
            
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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
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
            $admins = \App\Models\User::role(['Admin', 'Accountant', 'Manager'])->get();
            if ($admins->isEmpty()) {
                $admins = \App\Models\User::where('id', 1)->get();
            }
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
            'address' => 'required|string',
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
                $admins = \App\Models\User::role(['Admin', 'Accountant', 'Manager'])->get();
                if ($admins->isEmpty()) {
                    $admins = \App\Models\User::where('id', 1)->get();
                }
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

    public function show(Customer $customer, Request $request)
    {
        $customer->load(['sales' => function ($query) {
            $query->orderBy('date', 'desc');
        }]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $statementData = $this->getCustomerStatementData($customer, $startDate, $endDate);

        // For on-screen display, reverse to newest first, while preserving running balance
        $ledgerEntries = $statementData['entries']->sortByDesc(function($entry) {
            $parts = explode('_', $entry->id);
            $journalId = str_pad($parts[0], 10, '0', STR_PAD_LEFT);
            $subSeq = isset($parts[1]) ? $parts[1] : '0';
            return $entry->date . '_' . $journalId . '_' . $subSeq;
        })->values();

        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();

        $openingBalance = $statementData['opening_balance'];
        $totalDebit = $statementData['period_debit'];
        $totalCredit = $statementData['period_credit'];
        $finalRunningBalance = $statementData['closing_balance'];

        // Generate clean, secure short URL for WhatsApp sharing (valid for 90 days, no exposed customer ID)
        $stmtToken = StatementToken::createOrGetForCustomer($customer, $startDate, $endDate);
        $statementShortUrl = $stmtToken->getShortUrl();

        // Fallback signed URL
        $statementPdfSignedUrl = URL::temporarySignedRoute(
            'customer.statement.public',
            now()->addDays(60),
            [
                'customer' => $customer->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        return view('customers.show', compact(
            'customer',
            'ledgerEntries',
            'paymentMethods',
            'openingBalance',
            'finalRunningBalance',
            'totalDebit',
            'totalCredit',
            'startDate',
            'endDate',
            'statementShortUrl',
            'statementPdfSignedUrl',
            'statementData'
        ));
    }

    public function getCustomerStatementData(Customer $customer, ?string $startDate = null, ?string $endDate = null)
    {
        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        
        $journals = Journal::with(['entries.account', 'reference'])
            ->where(function($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\SalePayment::class)->whereIn('reference_id', \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->pluck('id'));
            })
            ->orderBy('date', 'asc')->orderBy('id', 'asc')
            ->get();

        // Filter out any journals referencing deleted sale invoices
        $journals = $journals->filter(function($j) {
            if (preg_match('/INV-[0-9\-]+/', $j->notes, $matches)) {
                $inv = $matches[0];
                return Sale::where('invoice_no', $inv)->exists();
            }
            return true;
        });

        $rawEntries = collect();

        foreach ($journals as $journal) {
            $debit = 0;
            $credit = 0;
            $journalDate = $journal->date ? \Carbon\Carbon::parse($journal->date)->format('Y-m-d') : date('Y-m-d');

            if ($journal->reference_type == Sale::class) {
                $sale = $journal->reference;
                if ($sale) {
                    if ($sale->total >= 0) {
                        $rawEntries->push((object)[
                            'id' => $journal->id . '_sale',
                            'journal' => $journal,
                            'date' => $journalDate,
                            'ref_no' => $sale->invoice_no ?: $journal->journal_no,
                            'notes' => 'Sale: ' . ($sale->invoice_no ?: $journal->notes),
                            'debit' => (float)$sale->total,
                            'credit' => 0,
                            'payment_method' => null,
                            'sort_order' => 1,
                        ]);
                    }

                    // Fallback for initial POS payment without separate journal
                    $initialPayments = \App\Models\SalePayment::where('sale_id', $sale->id)
                        ->where(function($q) {
                            $q->whereNull('reference')
                              ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                        })
                        ->get();
                    $initialPaymentAmount = (float)$initialPayments->sum('amount');

                    $hasJournal = $journals->contains(function($j) use ($sale) {
                        return str_contains($j->notes, 'Payment for POS Sale ' . $sale->invoice_no);
                    });

                    if ($initialPaymentAmount > 0 && !$hasJournal) {
                        $paymentJournal = clone $journal;
                        $paymentJournal->notes = 'Payment for ' . $sale->invoice_no;
                        
                        $initialPaymentMethods = $initialPayments->map(function($p) {
                            if (is_numeric($p->method)) {
                                $coa = \App\Models\ChartOfAccount::find($p->method);
                                return $coa ? $coa->name : $p->method;
                            }
                            return $p->method;
                        })->filter()->unique()->implode(', ');

                        $rawEntries->push((object)[
                            'id' => $journal->id . '_pay',
                            'journal' => $paymentJournal,
                            'date' => $journalDate,
                            'ref_no' => 'PAY-' . $sale->invoice_no,
                            'notes' => 'Payment for ' . $sale->invoice_no,
                            'debit' => 0,
                            'credit' => $initialPaymentAmount,
                            'payment_method' => $initialPaymentMethods ?: 'Cash',
                            'sort_order' => 2,
                        ]);
                    }
                }
                continue;
            } else {
                if ($journal->notes == 'Opening Balance') {
                    $debit = (float)$customer->opening_balance;
                } else {
                    $credit = (float)$journal->entries->where('account_id', $arId)->where('type', 'credit')->sum('amount');
                    $debit = (float)$journal->entries->where('account_id', $arId)->where('type', 'debit')->sum('amount');
                }
            }

            $internalTransferAmount = 0;
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

            if ($debit == 0 && $credit == 0 && $internalTransferAmount == 0 && $journal->notes != 'Opening Balance') {
                continue;
            }

            $notes = $journal->notes;
            if ($internalTransferAmount > 0) {
                $notes .= " (Wallet Used: ৳" . number_format($internalTransferAmount, 0) . ")";
            }

            $paymentMethod = null;
            if ($credit > 0 || $journal->reference_type == \App\Models\SalePayment::class || ($journal->reference_type == Customer::class && $journal->notes != 'Opening Balance')) {
                $paymentAccNames = $journal->entries
                    ->where('type', 'debit')
                    ->filter(function($entry) use ($arId) {
                        return $entry->account_id != $arId;
                    })
                    ->map(function($entry) {
                        return $entry->account ? $entry->account->name : null;
                    })
                    ->filter()->unique()->values()->all();

                if (!empty($paymentAccNames)) {
                    $paymentMethod = implode(', ', $paymentAccNames);
                } elseif ($journal->reference_type == \App\Models\SalePayment::class && $journal->reference) {
                    $methodVal = $journal->reference->method;
                    if (is_numeric($methodVal)) {
                        $coa = \App\Models\ChartOfAccount::find($methodVal);
                        $paymentMethod = $coa ? $coa->name : $methodVal;
                    } else {
                        $paymentMethod = $methodVal;
                    }
                } elseif ($internalTransferAmount > 0) {
                    $paymentMethod = 'Wallet Balance';
                }
            }

            $refNo = $journal->journal_no;
            if ($journal->reference_type == \App\Models\SalePayment::class && $journal->reference) {
                $refNo = $journal->reference->reference ?: $journal->journal_no;
            }

            $rawEntries->push((object)[
                'id' => (string)$journal->id,
                'journal' => $journal,
                'date' => $journalDate,
                'ref_no' => $refNo,
                'notes' => $notes,
                'debit' => $debit,
                'credit' => $credit,
                'payment_method' => $paymentMethod,
                'sort_order' => 3,
            ]);
        }

        // Sort all entries strictly chronologically
        $sortedEntries = $rawEntries->sortBy(function($entry) {
            $parts = explode('_', $entry->id);
            $journalId = str_pad($parts[0], 10, '0', STR_PAD_LEFT);
            return $entry->date . '_' . $journalId . '_' . ($entry->sort_order ?? 0);
        })->values();

        $openingBalance = 0;
        $periodEntries = collect();
        $periodDebit = 0;
        $periodCredit = 0;
        $allTimeDebit = 0;
        $allTimeCredit = 0;
        $runningBalance = 0;

        foreach ($sortedEntries as $entry) {
            $allTimeDebit += $entry->debit;
            $allTimeCredit += $entry->credit;

            if (!empty($startDate) && $entry->date < $startDate) {
                $openingBalance += ($entry->debit - $entry->credit);
                $runningBalance = $openingBalance;
            } else if ((empty($startDate) || $entry->date >= $startDate) && (empty($endDate) || $entry->date <= $endDate)) {
                $runningBalance += ($entry->debit - $entry->credit);
                $entryClone = clone $entry;
                $entryClone->running_balance = $runningBalance;
                $periodEntries->push($entryClone);
                $periodDebit += $entry->debit;
                $periodCredit += $entry->credit;
            }
        }

        $closingBalance = $openingBalance + $periodDebit - $periodCredit;

        return [
            'customer' => $customer,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'entries' => $periodEntries,
            'period_debit' => $periodDebit,
            'period_credit' => $periodCredit,
            'closing_balance' => $closingBalance,
            'all_time_debit' => $allTimeDebit,
            'all_time_credit' => $allTimeCredit,
            'final_running_balance' => $allTimeDebit - $allTimeCredit,
        ];
    }

    public function statementPdf(Customer $customer, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->getCustomerStatementData($customer, $startDate, $endDate);

        $logoPath = public_path('logo.webp');
        $data['logoBase64'] = file_exists($logoPath) ? 'data:image/webp;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $pdf = Pdf::loadView('customers.statement-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $periodStr = '';
        if ($startDate && $endDate) {
            $periodStr = '_' . $startDate . '_to_' . $endDate;
        } elseif ($startDate) {
            $periodStr = '_from_' . $startDate;
        } elseif ($endDate) {
            $periodStr = '_to_' . $endDate;
        }

        $filename = 'Statement_' . Str::slug($customer->name) . $periodStr . '.pdf';
        return $pdf->stream($filename);
    }

    public function publicStatementPdf(Customer $customer, Request $request)
    {
        if (!$request->hasValidSignature() && !auth()->check()) {
            abort(403, 'Invalid or expired statement download link.');
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->getCustomerStatementData($customer, $startDate, $endDate);

        $logoPath = public_path('logo.webp');
        $data['logoBase64'] = file_exists($logoPath) ? 'data:image/webp;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $pdf = Pdf::loadView('customers.statement-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $periodStr = '';
        if ($startDate && $endDate) {
            $periodStr = '_' . $startDate . '_to_' . $endDate;
        } elseif ($startDate) {
            $periodStr = '_from_' . $startDate;
        } elseif ($endDate) {
            $periodStr = '_to_' . $endDate;
        }

        $filename = 'Statement_' . Str::slug($customer->name) . $periodStr . '.pdf';
        return $pdf->stream($filename);
    }

    public function statementShortUrl(string $token)
    {
        $stmtToken = StatementToken::with('customer')->where('token', $token)->first();

        if (!$stmtToken || $stmtToken->isExpired() || !$stmtToken->customer) {
            abort(404, 'This statement link has expired or is invalid. Please contact Radhikas Trade International accounts for an updated statement.');
        }

        $stmtToken->increment('access_count');
        $stmtToken->update(['last_accessed_at' => now()]);

        $data = $this->getCustomerStatementData($stmtToken->customer, $stmtToken->start_date, $stmtToken->end_date);

        $logoPath = public_path('logo.webp');
        $data['logoBase64'] = file_exists($logoPath) ? 'data:image/webp;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $pdf = Pdf::loadView('customers.statement-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $periodStr = '';
        if ($stmtToken->start_date && $stmtToken->end_date) {
            $periodStr = '_' . $stmtToken->start_date . '_to_' . $stmtToken->end_date;
        } elseif ($stmtToken->start_date) {
            $periodStr = '_from_' . $stmtToken->start_date;
        } elseif ($stmtToken->end_date) {
            $periodStr = '_to_' . $stmtToken->end_date;
        }

        $filename = 'Statement_' . Str::slug($stmtToken->customer->name) . $periodStr . '.pdf';
        return $pdf->stream($filename);
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
            'address' => 'required|string',
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
        $customer->recalculateBalances();
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
