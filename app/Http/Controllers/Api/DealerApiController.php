<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerApiController extends Controller
{
    /**
     * Get paginated dealers & special dealers list with search, filter, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()
            ->whereIn('customer_type', ['dealer', 'special_dealer']);

        // Search by name, company, phone, district, or address
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by customer_type: 'dealer' or 'special_dealer'
        if ($request->filled('type') && in_array($request->type, ['dealer', 'special_dealer'])) {
            $query->where('customer_type', $request->type);
        }

        // Filter by district
        if ($request->filled('district') && strtolower($request->district) !== 'all') {
            $query->where('district', $request->district);
        }

        // Sorting
        $sortBy = in_array($request->get('sort_by'), ['name', 'company', 'district', 'id']) ? $request->get('sort_by') : 'name';
        $sortOrder = strtolower($request->get('sort_order')) === 'desc' ? 'desc' : 'asc';
        
        // Priority to special_dealer first, then sort
        $query->orderByRaw("FIELD(customer_type, 'special_dealer', 'dealer')")
              ->orderBy($sortBy, $sortOrder);

        $perPage = max(1, min((int) $request->get('per_page', 12), 100));
        $paginated = $query->paginate($perPage);

        // Transform collection to expose only public dealer info
        $items = $paginated->getCollection()->map(function ($dealer) {
            return [
                'id' => $dealer->id,
                'name' => $dealer->name,
                'company' => !empty($dealer->company) ? $dealer->company : null,
                'type' => $dealer->customer_type,
                'type_label' => $dealer->customer_type === 'special_dealer' ? 'Special Dealer' : 'Authorized Dealer',
                'is_special' => $dealer->customer_type === 'special_dealer',
                'phone' => $dealer->phone,
                'email' => !empty($dealer->email) ? $dealer->email : null,
                'district' => !empty($dealer->district) ? $dealer->district : null,
                'address' => !empty($dealer->address) ? $dealer->address : null,
            ];
        });

        // List of all active districts with dealers for quick frontend filter
        $districts = Customer::whereIn('customer_type', ['dealer', 'special_dealer'])
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->distinct()
            ->orderBy('district', 'asc')
            ->pluck('district')
            ->values();

        // Total count breakdown
        $counts = [
            'all' => Customer::whereIn('customer_type', ['dealer', 'special_dealer'])->count(),
            'dealer' => Customer::where('customer_type', 'dealer')->count(),
            'special_dealer' => Customer::where('customer_type', 'special_dealer')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'has_more' => $paginated->hasMorePages(),
            ],
            'districts' => $districts,
            'counts' => $counts,
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ]);
    }

    /**
     * Get distinct list of districts with dealer counts.
     */
    public function districts(): JsonResponse
    {
        $districts = Customer::whereIn('customer_type', ['dealer', 'special_dealer'])
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->selectRaw('district, count(*) as total_dealers')
            ->groupBy('district')
            ->orderBy('district', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $districts,
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ]);
    }
}
