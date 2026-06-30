<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DueSettlementController extends Controller
{
    public function customers(Request $request)
    {
        $query = Journal::with(['reference', 'entries.account', 'creator'])
                        ->where('reference_type', Customer::class)
                        ->orderByDesc('date');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('customer_id')) {
            $query->where('reference_id', $request->customer_id);
        }

        if ($request->filled('payment_method')) {
            $methodId = $request->payment_method;
            $query->whereHas('entries', function($q) use ($methodId) {
                $q->where('account_id', $methodId);
            });
        }
        
        $payments = $query->paginate(15)->withQueryString();
        $customersList = Customer::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return view('accounting.dues.customers', compact('payments', 'paymentMethods', 'customersList'));
    }

    public function payCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $totalPayment = (float) $validated['amount'];
        $walletAmount = min($totalPayment, $customer->wallet_balance);
        $amount = $totalPayment - $walletAmount;

        $totalPayment = $amount + $walletAmount;
        $duePayment = min($totalPayment, $customer->total_due);
        $newAdvance = max(0, $totalPayment - $customer->total_due);
        $netAdvance = $newAdvance - $walletAmount;

        try {
            DB::beginTransaction();

            if ($duePayment > 0) {
                $customer->decrement('total_due', $duePayment);
            }
            
            if ($netAdvance > 0) {
                $customer->increment('wallet_balance', $netAdvance);
            } elseif ($netAdvance < 0) {
                $customer->decrement('wallet_balance', abs($netAdvance));
            }

            // 1. Accounting first
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);
            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            $notes = $validated['notes'] ?? ('Payment received from Customer: ' . $customer->name . ($validated['reference'] ? ' (Ref: ' . $validated['reference'] . ')' : ''));

            $journal = Journal::create([
                'journal_no' => 'RCV-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            if ($amount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'debit',
                    'amount' => $amount,
                ]);
            }

            if ($walletAmount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'debit',
                    'amount' => $walletAmount,
                ]);
            }

            if ($duePayment > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'credit',
                    'amount' => $duePayment,
                ]);

                // 2. Distribute $duePayment among unpaid invoices
                $unpaidSales = \App\Models\Sale::where('customer_id', $customer->id)
                    ->where('due_amount', '>', 0)
                    ->orderBy('date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $remainingPayment = $duePayment;
                foreach ($unpaidSales as $sale) {
                    if ($remainingPayment <= 0) break;

                    $payThisSale = min($sale->due_amount, $remainingPayment);
                    
                    // Update Sale
                    $sale->paid_amount += $payThisSale;
                    $sale->due_amount -= $payThisSale;
                    $sale->payment_status = $sale->due_amount > 0 ? 'partial' : 'paid';
                    $sale->save();

                    // Create SalePayment
                    \App\Models\SalePayment::create([
                        'sale_id' => $sale->id,
                        'amount' => $payThisSale,
                        'method' => 'cash',
                        'date' => $validated['date'],
                        'reference' => 'Due Settlement ' . ($validated['reference'] ?? ''),
                    ]);

                    $remainingPayment -= $payThisSale;
                }
            }


            if ($newAdvance > 0) {
                $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function editCustomerPayment(Journal $journal)
    {
        $customersList = Customer::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        
        $cashEntry = $journal->entries()->where('type', 'debit')->whereHas('account', function($q) { $q->where('name', '!=', 'Customer Advance'); })->first();
        $amount = $cashEntry ? $cashEntry->amount : 0;
        $paymentMethodId = $cashEntry ? $cashEntry->account_id : null;

        $walletEntry = $journal->entries()->where('type', 'debit')->whereHas('account', function($q) { $q->where('name', 'Customer Advance'); })->first();
        $walletAmount = $walletEntry ? $walletEntry->amount : 0;

        $reference = '';
        if (str_contains($journal->notes, '(Ref: ')) {
            preg_match('/\(Ref: (.*?)\)/', $journal->notes, $matches);
            $reference = $matches[1] ?? '';
        }

        return view('accounting.dues.edit_customer_payment', compact('journal', 'customersList', 'paymentMethods', 'amount', 'walletAmount', 'paymentMethodId', 'reference'));
    }

    public function updateCustomerPayment(Request $request, Journal $journal)
    {
        // Updating a customer payment that affects invoices is highly complex and prone to edge cases.
        // The safest approach is to force the user to delete it (which runs our reverse logic) and recreate it.
        return redirect()->back()->with('error', 'To maintain invoice accuracy, please Delete this payment and create a new one instead of updating it.');
    }

    public function deleteCustomerPayment(Journal $journal)
    {
        DB::beginTransaction();
        try {
            $arEntry = $journal->entries()->where('type', 'credit')->whereHas('account', function($q) { $q->where('name', 'Accounts Receivable'); })->first();
            $duePayment = $arEntry ? $arEntry->amount : 0;
            
            $advCredit = $journal->entries()->where('type', 'credit')->whereHas('account', function($q) { $q->where('name', 'Customer Advance'); })->sum('amount');
            $advDebit = $journal->entries()->where('type', 'debit')->whereHas('account', function($q) { $q->where('name', 'Customer Advance'); })->sum('amount');
            $netAdvance = $advCredit - $advDebit;

            $customer = $journal->reference;
            if ($customer && $customer instanceof Customer) {
                if ($netAdvance > 0) {
                    $customer->decrement('wallet_balance', $netAdvance);
                } elseif ($netAdvance < 0) {
                    $customer->increment('wallet_balance', abs($netAdvance));
                }
                if ($duePayment > 0) {
                    $customer->increment('total_due', $duePayment);
                    
                    // Attempt to reverse SalePayments
                    $ref = '';
                    if (preg_match('/\(Ref: (.*?)\)/', $journal->notes, $matches)) {
                        $ref = $matches[1];
                    }
                    $salePaymentRef = 'Due Settlement ' . $ref;
                    
                    $salePayments = \App\Models\SalePayment::where('date', $journal->date)
                        ->where('reference', $salePaymentRef)
                        ->whereHas('sale', function($q) use ($customer) {
                            $q->where('customer_id', $customer->id);
                        })->get();
                        
                    foreach($salePayments as $sp) {
                        $sale = $sp->sale;
                        $sale->paid_amount -= $sp->amount;
                        $sale->due_amount += $sp->amount;
                        $sale->payment_status = $sale->paid_amount == 0 ? 'due' : 'partial';
                        $sale->save();
                        $sp->delete();
                    }
                }
            }

            $journal->entries()->delete();
            $journal->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Payment reversed and deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to delete payment: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    public function suppliers(Request $request)
    {
        $query = Journal::with(['reference', 'entries.account', 'creator'])
                        ->where('reference_type', Supplier::class)
                        ->orderByDesc('date');
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('supplier_id')) {
            $query->where('reference_id', $request->supplier_id);
        }

        if ($request->filled('payment_method')) {
            $methodId = $request->payment_method;
            $query->whereHas('entries', function($q) use ($methodId) {
                $q->where('account_id', $methodId);
            });
        }
        
        $payments = $query->paginate(15)->withQueryString();
        $suppliersList = Supplier::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return view('accounting.dues.suppliers', compact('payments', 'paymentMethods', 'suppliersList'));
    }

    public function paySupplier(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        try {
            DB::beginTransaction();

            $supplier->decrement('total_payable', $validated['amount']);

            // Accounting
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $apAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability']);

            $notes = $validated['notes'] ?? ('Payment made to Supplier: ' . $supplier->name . ($validated['reference'] ? ' (Ref: ' . $validated['reference'] . ')' : ''));

            $journal = Journal::create([
                'journal_no' => 'PAY-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Supplier::class,
                'reference_id' => $supplier->id,
                'notes' => $notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $apAcc->id,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAcc->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function editSupplierPayment(Journal $journal)
    {
        $suppliersList = Supplier::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        
        $apEntry = $journal->entries()->where('type', 'debit')->first();
        $cashEntry = $journal->entries()->where('type', 'credit')->first();
        $amount = $apEntry ? $apEntry->amount : 0;
        $paymentMethodId = $cashEntry ? $cashEntry->account_id : null;

        $reference = '';
        if (str_contains($journal->notes, '(Ref: ')) {
            preg_match('/\(Ref: (.*?)\)/', $journal->notes, $matches);
            $reference = $matches[1] ?? '';
        }

        return view('accounting.dues.edit_supplier_payment', compact('journal', 'suppliersList', 'paymentMethods', 'amount', 'paymentMethodId', 'reference'));
    }

    public function updateSupplierPayment(Request $request, Journal $journal)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $apEntry = $journal->entries()->where('type', 'debit')->first();
            $oldAmount = $apEntry ? $apEntry->amount : 0;
            $oldSupplierId = $journal->reference_id;

            if ($oldSupplierId) {
                Supplier::where('id', $oldSupplierId)->increment('total_payable', $oldAmount);
            }

            $newSupplier = Supplier::findOrFail($validated['supplier_id']);
            $newSupplier->decrement('total_payable', $validated['amount']);

            $journal->update([
                'date' => $validated['date'],
                'reference_id' => $newSupplier->id,
                'notes' => 'Payment made to Supplier: ' . $newSupplier->name . ($validated['reference'] ? ' (Ref: ' . $validated['reference'] . ')' : ''),
            ]);

            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $apAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability']);

            $journal->entries()->delete();

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $apAcc->id,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAcc->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            DB::commit();
            return redirect()->route('supplier-payables.index')->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update payment.');
        }
    }

    public function deleteSupplierPayment(Journal $journal)
    {
        DB::beginTransaction();
        try {
            $apEntry = $journal->entries()->where('type', 'debit')->first();
            $amount = $apEntry ? $apEntry->amount : 0;

            if ($journal->reference_type === Supplier::class && $journal->reference) {
                $journal->reference->increment('total_payable', $amount);
            }

            $journal->entries()->delete();
            $journal->delete();

            DB::commit();
            return redirect()->route('supplier-payables.index')->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete payment.');
        }
    }
}

