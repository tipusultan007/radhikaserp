<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SalaryPayment;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SalaryController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'salaryPayments'])
            ->orderBy('name')
            ->get();
            
        return view('salary.index', compact('users'));
    }

    public function show(User $user)
    {
        $payments = $user->salaryPayments()->latest('payment_date')->paginate(15);
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return view('salary.show', compact('user', 'payments', 'paymentMethods'));
    }

    public function storePayment(Request $request, User $user)
    {
        $validated = $request->validate([
            'payment_type'      => 'required|in:full,partial,advance',
            'payment_month'     => 'required|date_format:Y-m',
            'payment_date'      => 'required|date',
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'notes'             => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Find or Create Salary Accounts
            $salaryExpenseAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Salary & Wages Expense'],
                ['type' => 'expense', 'description' => 'Employee salaries and wages']
            );

            $salaryAdvanceAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Salary Advance'],
                ['type' => 'asset', 'description' => 'Salary advance paid to employees']
            );

            $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);

            $journal = Journal::create([
                'journal_no'     => 'SAL-' . strtoupper(Str::random(6)),
                'date'           => $validated['payment_date'],
                'reference_type' => User::class,
                'reference_id'   => $user->id,
                'notes'          => "Salary {$validated['payment_type']} for {$validated['payment_month']} - {$user->name}",
                'created_by'     => auth()->id() ?? 1,
            ]);

            $debitAccId = $validated['payment_type'] === 'advance' ? $salaryAdvanceAcc->id : $salaryExpenseAcc->id;

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccId,
                'type'       => 'debit',
                'amount'     => $validated['amount'],
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $paymentAcc->id,
                'type'       => 'credit',
                'amount'     => $validated['amount'],
            ]);

            $payment = new SalaryPayment($validated);
            $payment->user_id = $user->id;
            $payment->journal_id = $journal->id;
            $payment->created_by = auth()->id() ?? 1;
            $payment->save();

            DB::commit();
            return back()->with('success', 'Salary payment recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function updatePayment(Request $request, SalaryPayment $payment)
    {
        $validated = $request->validate([
            'payment_type'      => 'required|in:full,partial,advance',
            'payment_month'     => 'required|date_format:Y-m',
            'payment_date'      => 'required|date',
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'notes'             => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $payment->update($validated);

            if ($payment->journal_id) {
                $journal = Journal::find($payment->journal_id);
                if ($journal) {
                    $journal->update([
                        'date'  => $validated['payment_date'],
                        'notes' => "Salary {$validated['payment_type']} for {$validated['payment_month']} - {$payment->user->name}",
                    ]);

                    $journal->entries()->delete();

                    $salaryExpenseAcc = ChartOfAccount::firstOrCreate(
                        ['name' => 'Salary & Wages Expense'],
                        ['type' => 'expense', 'description' => 'Employee salaries and wages']
                    );

                    $salaryAdvanceAcc = ChartOfAccount::firstOrCreate(
                        ['name' => 'Salary Advance'],
                        ['type' => 'asset', 'description' => 'Salary advance paid to employees']
                    );

                    $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);
                    
                    $debitAccId = $validated['payment_type'] === 'advance' ? $salaryAdvanceAcc->id : $salaryExpenseAcc->id;

                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'account_id' => $debitAccId,
                        'type'       => 'debit',
                        'amount'     => $validated['amount'],
                    ]);

                    JournalEntry::create([
                        'journal_id' => $journal->id,
                        'account_id' => $paymentAcc->id,
                        'type'       => 'credit',
                        'amount'     => $validated['amount'],
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Salary payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroyPayment(SalaryPayment $payment)
    {
        try {
            DB::beginTransaction();
            if ($payment->journal_id) {
                $journal = Journal::find($payment->journal_id);
                if ($journal) {
                    $journal->entries()->delete();
                    $journal->delete();
                }
            }
            $payment->delete();
            DB::commit();
            return back()->with('success', 'Salary payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

