<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTargetScheme;
use App\Models\CustomerTargetItem;
use App\Models\Product;
use App\Services\CustomerTargetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerTargetController extends Controller
{
    protected CustomerTargetService $targetService;

    public function __construct(CustomerTargetService $targetService)
    {
        $this->targetService = $targetService;
    }

    /**
     * Display a listing of customer target schemes.
     */
    public function index(Request $request)
    {
        $query = CustomerTargetScheme::with(['creator', 'items.product', 'items.productVariant'])
            ->withCount('items');

        if ($request->filled('month')) {
            $query->where('target_month', $request->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $schemes = $query->latest('target_month')->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => CustomerTargetScheme::count(),
            'active' => CustomerTargetScheme::where('status', 'active')->count(),
            'current_month' => CustomerTargetScheme::where('target_month', now()->format('Y-m'))->count(),
            'total_disbursed' => \App\Models\CustomerBonus::where('status', 'disbursed')->sum('bonus_amount'),
        ];

        return view('customer_targets.index', compact('schemes', 'stats'));
    }

    /**
     * Show the form for creating a new target scheme.
     */
    public function create()
    {
        $products = Product::with(['variants', 'unit'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::orderBy('name')->get();

        $currentMonth = now()->format('Y-m');
        $startDate = now()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        return view('customer_targets.create', compact('products', 'customers', 'currentMonth', 'startDate', 'endDate'));
    }

    /**
     * Store a newly created target scheme in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_month' => 'nullable|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,cancelled',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.target_qty' => 'required|numeric|min:0.01',
            'items.*.bonus_per_unit' => 'required|numeric|min:0.01',
        ], [
            'items.required' => 'Please add at least one product target.',
            'items.min' => 'Please add at least one product target.',
            'items.*.customer_id.required' => 'Each row must have a customer selected.',
        ]);

        DB::transaction(function () use ($validated) {
            $scheme = CustomerTargetScheme::create([
                'name' => $validated['name'],
                'target_month' => !empty($validated['target_month']) ? $validated['target_month'] : Carbon::parse($validated['start_date'])->format('Y-m'),
                'start_date' => $validated['start_date'],
                'end_date' => !empty($validated['end_date']) ? $validated['end_date'] : null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                CustomerTargetItem::create([
                    'customer_target_scheme_id' => $scheme->id,
                    'customer_id' => $item['customer_id'],
                    'product_id' => $item['product_id'],
                    'target_qty' => $item['target_qty'],
                    'bonus_per_unit' => $item['bonus_per_unit'],
                ]);
            }
        });

        return redirect()->route('customer-targets.index')->with('success', 'Customer target campaign created successfully.');
    }

    /**
     * Display the specified target scheme and its customer progress report.
     */
    public function show(CustomerTargetScheme $customerTarget, Request $request)
    {
        $customerTarget->load(['creator', 'items.product.unit', 'items.productVariant.unit']);

        $report = $this->targetService->getSchemeReport($customerTarget, $request->search);

        return view('customer_targets.show', compact('customerTarget', 'report'));
    }

    /**
     * Show the form for editing the specified target scheme.
     */
    public function edit(CustomerTargetScheme $customerTarget)
    {
        $customerTarget->load('items.customer');

        $products = Product::with(['variants', 'unit'])
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::orderBy('name')->get();

        return view('customer_targets.edit', compact('customerTarget', 'products', 'customers'));
    }

    /**
     * Update the specified target scheme in storage.
     */
    public function update(Request $request, CustomerTargetScheme $customerTarget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_month' => 'nullable|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,cancelled',
            'items' => 'required|array|min:1',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.target_qty' => 'required|numeric|min:0.01',
            'items.*.bonus_per_unit' => 'required|numeric|min:0.01',
        ], [
            'items.*.customer_id.required' => 'Each row must have a customer selected.',
        ]);

        DB::transaction(function () use ($validated, $customerTarget) {
            $customerTarget->update([
                'name' => $validated['name'],
                'target_month' => !empty($validated['target_month']) ? $validated['target_month'] : Carbon::parse($validated['start_date'])->format('Y-m'),
                'start_date' => $validated['start_date'],
                'end_date' => !empty($validated['end_date']) ? $validated['end_date'] : null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ]);

            // Replace items
            $customerTarget->items()->delete();

            foreach ($validated['items'] as $item) {
                CustomerTargetItem::create([
                    'customer_target_scheme_id' => $customerTarget->id,
                    'customer_id' => $item['customer_id'],
                    'product_id' => $item['product_id'],
                    'target_qty' => $item['target_qty'],
                    'bonus_per_unit' => $item['bonus_per_unit'],
                ]);
            }
        });

        return redirect()->route('customer-targets.show', $customerTarget->id)->with('success', 'Customer target campaign updated successfully.');
    }

    /**
     * Remove the specified target scheme from storage.
     */
    public function destroy(CustomerTargetScheme $customerTarget)
    {
        // Check if any bonuses were already disbursed
        $hasDisbursed = $customerTarget->bonuses()->where('status', 'disbursed')->exists();
        if ($hasDisbursed) {
            return redirect()->back()->with('error', 'Cannot delete campaign because bonuses have already been disbursed to customers.');
        }

        $customerTarget->delete();

        return redirect()->route('customer-targets.index')->with('success', 'Customer target campaign deleted successfully.');
    }

    /**
     * Disburse bonus for a single customer.
     */
    public function disburse(Request $request, CustomerTargetScheme $customerTarget, Customer $customer)
    {
        try {
            $notes = $request->input('notes');
            $bonus = $this->targetService->disburseBonus($customerTarget, $customer, auth()->user(), $notes);

            return redirect()->back()->with('success', "Bonus of BDT {$bonus->bonus_amount} successfully credited to {$customer->name}'s wallet!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to disburse bonus: ' . $e->getMessage());
        }
    }

    /**
     * Batch disburse bonus to all eligible customers.
     */
    public function disburseAll(CustomerTargetScheme $customerTarget)
    {
        try {
            $result = $this->targetService->disburseAllEligible($customerTarget, auth()->user());

            if ($result['count'] > 0) {
                return redirect()->back()->with('success', "Successfully disbursed BDT {$result['total_amount']} to {$result['count']} customers' wallets!");
            } else {
                return redirect()->back()->with('info', 'No pending eligible customers found for disbursement.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Batch disbursement failed: ' . $e->getMessage());
        }
    }

    /**
     * Stop an active/ongoing campaign.
     */
    public function stop(CustomerTargetScheme $customerTarget)
    {
        $customerTarget->update([
            'status' => 'completed',
            'end_date' => $customerTarget->end_date ?: now()->toDateString(),
        ]);

        return redirect()->back()->with('success', "Campaign '{$customerTarget->name}' has been stopped successfully.");
    }
}

