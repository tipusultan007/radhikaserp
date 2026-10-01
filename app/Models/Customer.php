<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'customer_type', 'email', 'password', 'phone', 'address', 'district', 'company', 'credit_limit', 'total_due', 'opening_balance', 'wallet_balance'])]
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use \App\Traits\LogsActivity;

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'total_due' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function bonuses()
    {
        return $this->hasMany(CustomerBonus::class);
    }

    public function recalculateBalances(?int $excludeSaleId = null): self
    {
        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;

        $salesQuery = $this->sales();
        if ($excludeSaleId) {
            $salesQuery->where('id', '!=', $excludeSaleId);
        }
        $sales = $salesQuery->get();
        $saleIds = $sales->pluck('id')->toArray();
        $salePaymentIds = SalePayment::whereIn('sale_id', $saleIds)->pluck('id')->toArray();

        $journals = Journal::with(['entries'])
            ->where(function($q) {
                $q->where('reference_type', Customer::class)->where('reference_id', $this->id);
            })->orWhere(function($q) use ($saleIds) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $saleIds);
            })->orWhere(function($q) use ($salePaymentIds) {
                $q->where('reference_type', SalePayment::class)->whereIn('reference_id', $salePaymentIds);
            })
            ->get();

        $calculatedDue = (float) $this->opening_balance + (float) $sales->sum('total');
        $advCredit = 0;
        $advDebit = 0;

        foreach ($journals as $journal) {
            // Defensive: Skip if this journal mentions a deleted or excluded sale invoice
            if (preg_match('/INV-[0-9\-]+/', $journal->notes, $matches)) {
                $inv = $matches[0];
                $saleRecord = Sale::where('invoice_no', $inv)->first();
                if (!$saleRecord || ($excludeSaleId && $saleRecord->id == $excludeSaleId)) {
                    continue;
                }
            }

            if ($journal->notes != 'Opening Balance') {
                $advCredit += (float) $journal->entries->where('account_id', $advId)->where('type', 'credit')->sum('amount');
                $advDebit += (float) $journal->entries->where('account_id', $advId)->where('type', 'debit')->sum('amount');
            }

            // Subtract payments recorded via non-sale journals (like Customer or SalePayment)
            if ($journal->reference_type != Sale::class && $journal->notes != 'Opening Balance') {
                $credit = (float) $journal->entries->where('account_id', $arId)->where('type', 'credit')->sum('amount');
                $debit = (float) $journal->entries->where('account_id', $arId)->where('type', 'debit')->sum('amount');

                if ($debit > 0 && $credit > 0) {
                    if ($debit > $credit) {
                        $debit = $debit - $credit;
                        $credit = 0;
                    } elseif ($credit > $debit) {
                        $credit = $credit - $debit;
                        $debit = 0;
                    } else {
                        $debit = 0;
                        $credit = 0;
                    }
                }

                $calculatedDue -= $credit;
                $calculatedDue += $debit;
            }
        }

        // Subtract fallback POS payments for sales without separate payment journals
        $posPayments = 0;
        foreach ($sales as $sale) {
            $initialPaymentAmount = (float) SalePayment::where('sale_id', $sale->id)
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

        $this->total_due = max(0, $calculatedDue);
        $this->wallet_balance = max(0, $calculatedWallet);
        $this->save();

        return $this;
    }
}
