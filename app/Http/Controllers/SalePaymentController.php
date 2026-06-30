<?php

namespace App\Http\Controllers;

use App\Models\SalePayment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalePaymentController extends Controller
{
    public function index(Sale $sale)
    {
        $payments = SalePayment::where('sale_id', $sale->id)->orderBy('date', 'desc')->get();
        return response()->json($payments);
    }

    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|exists:chart_of_accounts,id',
        ]);

        try {
            DB::beginTransaction();

            $newAmount = (float) $validated['amount'];
            if ($newAmount > $sale->due_amount) {
                return back()->withErrors(['error' => 'Payment cannot exceed the total invoice due of $' . number_format($sale->due_amount, 2)]);
            }

            // Create Payment
            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $newAmount,
                'method' => ChartOfAccount::find($validated['method'])->name ?? 'cash',
                'date' => now()->toDateString(),
                'reference' => 'Invoice Payment',
            ]);

            // Update Sale
            $sale->paid_amount += $newAmount;
            $sale->due_amount -= $newAmount;
            $sale->payment_status = $sale->due_amount > 0 ? ($sale->paid_amount > 0 ? 'partial' : 'due') : 'paid';
            $sale->save();

            // Update Customer Total Due
            if ($sale->customer) {
                $sale->customer->decrement('total_due', $newAmount);
            }

            // Accounting
            $cashAcc = ChartOfAccount::find($validated['method']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'PAY-' . strtoupper(Str::random(6)),
                'date' => now()->toDateString(),
                'reference_type' => SalePayment::class,
                'reference_id' => $payment->id,
                'notes' => 'Payment for Sale ' . $sale->invoice_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $newAmount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'credit', 'amount' => $newAmount]);

            DB::commit();
            return back()->with('success', 'Payment added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to add payment: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $payment = SalePayment::findOrFail($id);
            $sale = $payment->sale;
            $customer = $sale->customer;

            $oldAmount = $payment->amount;
            $newAmount = (float) $validated['amount'];
            $difference = $newAmount - $oldAmount;

            if ($difference == 0) {
                return back()->with('info', 'Amount is the same.');
            }

            // Check if increased amount exceeds due
            // Wait, we can't pay more than the invoice due_amount (excluding the old payment).
            $maxAllowed = $sale->due_amount + $oldAmount;
            if ($newAmount > $maxAllowed) {
                return back()->withErrors(['error' => 'Payment cannot exceed the total invoice due of $' . number_format($maxAllowed, 2)]);
            }

            // Update Payment
            $payment->amount = $newAmount;
            $payment->save();

            // Update Sale
            $sale->paid_amount += $difference;
            $sale->due_amount -= $difference;
            $sale->payment_status = $sale->due_amount > 0 ? ($sale->paid_amount > 0 ? 'partial' : 'due') : 'paid';
            $sale->save();

            // Update Customer Total Due
            // Difference > 0 means paid more, so due decreases.
            if ($difference > 0) {
                $customer->decrement('total_due', $difference);
            } else {
                $customer->increment('total_due', abs($difference));
            }

            // Accounting
            $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'ADJ-' . strtoupper(Str::random(6)),
                'date' => now()->toDateString(),
                'reference_type' => SalePayment::class,
                'reference_id' => $payment->id,
                'notes' => 'Payment adjustment for Sale ' . $sale->invoice_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            if ($difference > 0) {
                // Increased payment: Debit Cash, Credit AR
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $difference]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'credit', 'amount' => $difference]);
            } else {
                // Decreased payment: Debit AR, Credit Cash
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => abs($difference)]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'credit', 'amount' => abs($difference)]);
            }

            DB::commit();
            return back()->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update payment: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $payment = SalePayment::findOrFail($id);
            $sale = $payment->sale;
            $customer = $sale->customer;
            $amount = $payment->amount;

            // Revert Sale
            $sale->paid_amount -= $amount;
            $sale->due_amount += $amount;
            $sale->payment_status = $sale->due_amount > 0 ? ($sale->paid_amount > 0 ? 'partial' : 'due') : 'paid';
            $sale->save();

            // Revert Customer Total Due
            $customer->increment('total_due', $amount);

            // Accounting Reverse
            $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'REV-' . strtoupper(Str::random(6)),
                'date' => now()->toDateString(),
                'reference_type' => SalePayment::class,
                'reference_id' => $payment->id,
                'notes' => 'Payment reversed for Sale ' . $sale->invoice_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Reverse the payment: Debit AR, Credit Cash
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $amount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'credit', 'amount' => $amount]);

            $payment->delete();

            DB::commit();
            return back()->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete payment: ' . $e->getMessage()]);
        }
    }
}

