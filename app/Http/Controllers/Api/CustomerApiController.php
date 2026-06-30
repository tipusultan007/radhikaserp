<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;

class CustomerApiController extends Controller
{
    /**
     * Get all products with variants.
     */
    public function products()
    {
        $products = Product::with(['variants' => function ($query) {
            $query->where('status', true);
        }])->where('status', true)->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Create a pending order.
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $customer = $request->user();

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $total_price = $item['qty'] * $item['unit_price'];
                $subtotal += $total_price;

                $itemsData[] = [
                    'product_variant_id' => $item['product_variant_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $total_price,
                ];
            }

            // Create pending sale (order)
            $sale = Sale::create([
                'invoice_no' => 'ORD-' . strtoupper(uniqid()),
                'customer_id' => $customer->id,
                'warehouse_id' => 1, // Default warehouse or null if nullable
                'date' => now()->toDateString(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'paid_amount' => 0,
                'due_amount' => $subtotal,
                'payment_status' => 'due',
                'created_by' => \App\Models\User::first()->id ?? 1, // Fallback to 1 if no admin exists
                'source' => 'customer',
                'estimate_delivery_date' => $request->input('estimate_delivery_date'),
                'shipping_address' => $request->input('shipping_address'),
            ]);

            // Create sale items
            foreach ($itemsData as $itemData) {
                $itemData['sale_id'] = $sale->id;
                SaleItem::create($itemData);
            }

            // Update customer total due
            $customer->total_due += $subtotal;
            $customer->save();

            DB::commit();

            try {
                $admins = \App\Models\User::all();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewOrderNotification($sale));
            } catch (\Exception $e) {
                // Ignore notification errors to not break order placement
            }

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => $sale->load('items.productVariant')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders for the authenticated customer.
     */
    public function orders(Request $request)
    {
        $orders = Sale::where('customer_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Get order details.
     */
    public function orderDetails(Request $request, $id)
    {
        $order = Sale::with(['items.productVariant.product', 'payments'])
            ->where('customer_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    /**
     * Get all payments made by the customer.
     */
    public function payments(Request $request)
    {
        // Fetch all payments for this customer's sales
        $payments = SalePayment::whereHas('sale', function ($query) use ($request) {
            $query->where('customer_id', $request->user()->id);
        })->with('sale:id,invoice_no,total')->orderBy('id', 'desc')->get();

        return response()->json(['payments' => $payments]);
    }

    /**
     * Get customer due amount.
     */
    public function dues(Request $request)
    {
        $customer = $request->user();
        
        return response()->json([
            'total_due' => $customer->total_due,
            'wallet_balance' => $customer->wallet_balance,
            'credit_limit' => $customer->credit_limit,
            'customer' => $customer,
        ]);
    }

    /**
     * Change customer password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $customer = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $customer->password)) {
            return response()->json(['error' => 'Incorrect old password.'], 400);
        }

        $customer->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $customer->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }
}

