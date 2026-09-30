<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBonus;
use App\Models\CustomerTargetScheme;
use App\Models\CustomerTargetItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerTargetService
{
    /**
     * Calculate monthly purchase progress for a single customer against a target scheme.
     */
    public function calculateCustomerProgress(Customer $customer, CustomerTargetScheme $scheme): array
    {
        // Fetch all qualifying sale IDs for this customer within the scheme date range
        $salesQuery = Sale::where('customer_id', $customer->id)
            ->where('delivery_status', '!=', 'cancelled');

        if ($scheme->start_date) {
            $salesQuery->whereDate('date', '>=', $scheme->start_date);
        }

        if ($scheme->end_date) {
            $salesQuery->whereDate('date', '<=', $scheme->end_date);
        }

        $saleIds = $salesQuery->pluck('id');

        // Fetch all sale items with product and variant info
        $saleItems = SaleItem::with('productVariant')
            ->whereIn('sale_id', $saleIds)
            ->get();

        $itemsBreakdown = [];
        $totalTargetQty = 0;
        $totalAchievedQty = 0;
        $allTargetsMet = true;
        $totalBonusEarned = 0;

        // Filter items: customer-specific rows for this customer OR global rows (no customer_id)
        $schemeItems = $scheme->items()
            ->with(['product.unit', 'productVariant.unit', 'customer'])
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhereNull('customer_id');
            })
            ->get();

        if ($schemeItems->isEmpty()) {
            return [
                'customer' => $customer,
                'items_breakdown' => [],
                'total_target_qty' => 0,
                'total_achieved_qty' => 0,
                'progress_percent' => 0,
                'is_target_met' => false,
                'total_bonus_amount' => 0,
                'bonus_record' => null,
            ];
        }

        foreach ($schemeItems as $targetItem) {
            $matchingQty = 0;

            foreach ($saleItems as $sItem) {
                if ($targetItem->product_variant_id) {
                    if ($sItem->product_variant_id == $targetItem->product_variant_id) {
                        $matchingQty += (float) $sItem->qty;
                    }
                } else {
                    // Match any variant of this product
                    if ($sItem->productVariant && $sItem->productVariant->product_id == $targetItem->product_id) {
                        $matchingQty += (float) $sItem->qty;
                    }
                }
            }

            $targetQty = (float) $targetItem->target_qty;
            $bonusPerUnit = (float) $targetItem->bonus_per_unit;
            $itemMet = ($targetQty > 0) && ($matchingQty >= $targetQty);

            if (!$itemMet) {
                $allTargetsMet = false;
            }

            // Bonus is earned on achieved quantity once target threshold is fulfilled
            $itemBonus = $itemMet ? round($matchingQty * $bonusPerUnit, 2) : 0;
            $totalBonusEarned += $itemBonus;

            $totalTargetQty += $targetQty;
            $totalAchievedQty += $matchingQty;

            $itemProgress = $targetQty > 0 ? min(100, round(($matchingQty / $targetQty) * 100, 1)) : 100;

            $itemsBreakdown[] = [
                'item_id' => $targetItem->id,
                'product_id' => $targetItem->product_id,
                'product_name' => $targetItem->product ? $targetItem->product->name : 'N/A',
                'product_image' => $targetItem->product ? $targetItem->product->image_url : null,
                'unit' => $targetItem->product && $targetItem->product->unit ? $targetItem->product->unit->name : 'pcs',
                'product_variant_id' => $targetItem->product_variant_id,
                'variant_name' => $targetItem->productVariant ? $targetItem->productVariant->name : 'All Variants',
                'target_qty' => $targetQty,
                'achieved_qty' => $matchingQty,
                'progress_percent' => $itemProgress,
                'is_target_met' => $itemMet,
                'bonus_per_unit' => $bonusPerUnit,
                'bonus_amount' => $itemBonus,
            ];
        }

        $overallProgress = $totalTargetQty > 0 ? min(100, round(($totalAchievedQty / $totalTargetQty) * 100, 1)) : 0;

        // Check if a bonus record already exists
        $bonusRecord = CustomerBonus::where('customer_target_scheme_id', $scheme->id)
            ->where('customer_id', $customer->id)
            ->first();

        return [
            'customer' => $customer,
            'items_breakdown' => $itemsBreakdown,
            'total_target_qty' => $totalTargetQty,
            'total_achieved_qty' => $totalAchievedQty,
            'progress_percent' => $overallProgress,
            'is_target_met' => $allTargetsMet,
            'total_bonus_amount' => $allTargetsMet ? round($totalBonusEarned, 2) : 0,
            'bonus_record' => $bonusRecord,
        ];
    }

    /**
     * Get aggregated scheme report for all customers.
     */
    public function getSchemeReport(CustomerTargetScheme $scheme, ?string $search = null): array
    {
        // Only fetch customers who have a specific target row in this scheme
        $customerIds = CustomerTargetItem::where('customer_target_scheme_id', $scheme->id)
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        // Also include customers who already have bonus records for this scheme
        $bonusCustomerIds = \App\Models\CustomerBonus::where('customer_target_scheme_id', $scheme->id)
            ->pluck('customer_id');

        $allCustomerIds = $customerIds->merge($bonusCustomerIds)->unique();

        $customerQuery = Customer::whereIn('id', $allCustomerIds);

        if ($search) {
            $customerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $customers = $customerQuery->orderBy('name')->get();

        $customerReports = [];
        $totalAchievedCampaign = 0;
        $totalEligibleCustomers = 0;
        $totalBonusEarnedCampaign = 0;
        $totalBonusDisbursedCampaign = 0;

        foreach ($customers as $customer) {
            $progress = $this->calculateCustomerProgress($customer, $scheme);

            if ($progress['total_achieved_qty'] > 0 || $progress['bonus_record'] || $progress['total_target_qty'] > 0) {
                $customerReports[] = $progress;
                $totalAchievedCampaign += $progress['total_achieved_qty'];

                if ($progress['is_target_met']) {
                    $totalEligibleCustomers++;
                    $totalBonusEarnedCampaign += $progress['total_bonus_amount'];
                }

                if ($progress['bonus_record'] && $progress['bonus_record']->status === 'disbursed') {
                    $totalBonusDisbursedCampaign += (float) $progress['bonus_record']->bonus_amount;
                }
            }
        }

        // Sort: Customers meeting target first, then by highest achieved qty
        usort($customerReports, function ($a, $b) {
            if ($a['is_target_met'] !== $b['is_target_met']) {
                return $b['is_target_met'] <=> $a['is_target_met'];
            }
            return $b['total_achieved_qty'] <=> $a['total_achieved_qty'];
        });

        return [
            'customers' => $customerReports,
            'total_participating' => count($customerReports),
            'total_eligible' => $totalEligibleCustomers,
            'total_achieved_qty' => $totalAchievedCampaign,
            'total_bonus_earned' => $totalBonusEarnedCampaign,
            'total_bonus_disbursed' => $totalBonusDisbursedCampaign,
        ];
    }

    /**
     * Disburse bonus to customer wallet with double-entry accounting.
     */
    public function disburseBonus(CustomerTargetScheme $scheme, Customer $customer, $adminUser = null, ?string $notes = null): CustomerBonus
    {
        $progress = $this->calculateCustomerProgress($customer, $scheme);

        if (!$progress['is_target_met']) {
            throw new \Exception("Customer has not fulfilled the target requirements for this scheme.");
        }

        $bonusAmount = (float) $progress['total_bonus_amount'];
        if ($bonusAmount <= 0) {
            throw new \Exception("Calculated bonus amount is zero.");
        }

        // Check existing record
        $existingBonus = CustomerBonus::where('customer_target_scheme_id', $scheme->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($existingBonus && $existingBonus->status === 'disbursed') {
            throw new \Exception("Bonus for this scheme has already been disbursed to this customer.");
        }

        return DB::transaction(function () use ($scheme, $customer, $progress, $bonusAmount, $adminUser, $notes, $existingBonus) {
            // Find or create accounts
            $bonusExpenseAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Customer Bonus Expense'],
                ['type' => 'expense', 'is_payment_method' => false]
            );

            $custAdvanceAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Customer Advance'],
                ['type' => 'liability', 'is_payment_method' => false]
            );

            // Increment customer wallet balance
            $customer->increment('wallet_balance', $bonusAmount);

            $userId = $adminUser ? $adminUser->id : (auth()->id() ?: 1);

            // Create Journal entry
            $journalNo = 'BON-' . strtoupper(Str::random(6));
            $journal = Journal::create([
                'journal_no' => $journalNo,
                'date' => now()->toDateString(),
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'notes' => $notes ?: ("Target Bonus for: {$scheme->name}" . ($scheme->target_month ? " ({$scheme->target_month})" : '') . " - BDT {$bonusAmount}"),
                'created_by' => $userId,
            ]);

            // Debit: Customer Bonus Expense
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $bonusExpenseAcc->id,
                'type' => 'debit',
                'amount' => $bonusAmount,
            ]);

            // Credit: Customer Advance (Wallet Liability)
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $custAdvanceAcc->id,
                'type' => 'credit',
                'amount' => $bonusAmount,
            ]);

            // Create or update CustomerBonus record
            $bonusData = [
                'customer_target_scheme_id' => $scheme->id,
                'customer_id' => $customer->id,
                'achieved_qty' => $progress['total_achieved_qty'],
                'target_qty' => $progress['total_target_qty'],
                'is_target_met' => true,
                'bonus_amount' => $bonusAmount,
                'status' => 'disbursed',
                'disbursed_at' => now(),
                'disbursed_by' => $userId,
                'journal_id' => $journal->id,
                'notes' => $notes,
            ];

            if ($existingBonus) {
                $existingBonus->update($bonusData);
                $bonusRecord = $existingBonus;
            } else {
                $bonusRecord = CustomerBonus::create($bonusData);
            }

            // Log activity
            ActivityLog::create([
                'user_id' => $userId,
                'action' => 'customer_bonus_disbursed',
                'description' => "Disbursed target bonus of BDT {$bonusAmount} to customer {$customer->name} (Wallet Balance: BDT {$customer->wallet_balance})",
                'reference_type' => CustomerBonus::class,
                'reference_id' => $bonusRecord->id,
                'ip_address' => request() ? request()->ip() : '127.0.0.1',
            ]);

            return $bonusRecord;
        });
    }

    /**
     * Batch disburse all eligible customers for a target scheme.
     */
    public function disburseAllEligible(CustomerTargetScheme $scheme, $adminUser = null): array
    {
        $report = $this->getSchemeReport($scheme);
        $count = 0;
        $totalDisbursed = 0;

        foreach ($report['customers'] as $custData) {
            if ($custData['is_target_met']) {
                $isAlreadyDisbursed = $custData['bonus_record'] && $custData['bonus_record']->status === 'disbursed';
                if (!$isAlreadyDisbursed && $custData['total_bonus_amount'] > 0) {
                    try {
                        $this->disburseBonus($scheme, $custData['customer'], $adminUser);
                        $count++;
                        $totalDisbursed += $custData['total_bonus_amount'];
                    } catch (\Exception $e) {
                        // Continue to next customer if one fails
                        continue;
                    }
                }
            }
        }

        return [
            'count' => $count,
            'total_amount' => $totalDisbursed,
        ];
    }
}
