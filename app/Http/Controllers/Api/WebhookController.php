<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Steadfast Courier status updates.
     */
    public function steadfast(Request $request)
    {
        // Verify Webhook Authentication Token
        $expectedToken = config('services.steadfast.webhook_token');
        if (!empty($expectedToken)) {
            // Steadfast typically passes the token via Bearer token, X-Auth-Token header, api-key header, or query/body token parameter
            $incomingToken = $request->bearerToken() 
                ?: $request->header('X-Auth-Token') 
                ?: $request->header('Api-Key') 
                ?: $request->header('Authorization')
                ?: $request->input('token')
                ?: $request->input('auth_token');

            if (!$incomingToken || !hash_equals($expectedToken, trim(str_replace('Bearer ', '', $incomingToken)))) {
                Log::warning('Steadfast Webhook unauthorized access attempt', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all()
                ]);
                return response()->json(['error' => 'Unauthorized: Invalid auth token'], 401);
            }
        }

        // Steadfast webhook payload typically looks like:
        // {
        //   "consignment_id": "STDFST...",
        //   "status": "delivered", // or "in_transit", "cancelled", etc.
        //   "tracking_code": "..."
        // }

        $consignmentId = $request->input('consignment_id');
        $status = $request->input('status');
        $trackingMessage = $request->input('tracking_message');

        Log::info('Steadfast Webhook Received', $request->all());

        if (!$consignmentId || (!$status && !$trackingMessage)) {
            Log::info('Steadfast Webhook ignored: Missing consignment_id or both status/tracking_message', $request->all());
            return response()->json(['error' => 'Missing consignment_id or status/tracking_message'], 400);
        }

        // Find the sale with this consignment_id
        $sale = Sale::where('consignment_id', $consignmentId)->first();

        if (!$sale) {
            Log::warning("Steadfast Webhook failed: Sale not found for consignment_id {$consignmentId}");
            return response()->json(['error' => 'Sale not found'], 404);
        }

        if ($status) {
            // Map Steadfast status to our delivery_status
            $newStatus = $this->mapSteadfastStatus($status);

            if ($newStatus) {
                $oldStatus = $sale->delivery_status;
                $sale->delivery_status = $newStatus;

                if ($newStatus === 'shipped' && !$sale->dispatched_at) {
                    $sale->dispatched_at = now();
                    $sale->dispatched_by = 1;
                }

                if ($newStatus === 'delivered') {
                    if (!$sale->dispatched_at) {
                        $sale->dispatched_at = now();
                        $sale->dispatched_by = 1;
                    }
                    if (!$sale->delivered_at) {
                        $sale->delivered_at = now();
                        $sale->delivered_by = 1;
                    }
                }

                Log::info("Sale #{$sale->invoice_no} delivery_status updated to {$newStatus}");
            }
        }

        if ($trackingMessage) {
            $updates = $sale->tracking_updates ?? [];
            $updates[] = [
                'status' => $status ?? 'tracking_update',
                'message' => $trackingMessage,
                'date' => now()->toDateTimeString()
            ];
            $sale->tracking_updates = $updates;
        }

        $sale->save();

        if (isset($newStatus)) {
            $hasInventoryTxns = \App\Models\InventoryTransaction::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->exists();

            $isDispatchedOrDelivered = in_array($newStatus, ['shipped', 'delivered']);

            if ($isDispatchedOrDelivered && !$hasInventoryTxns) {
                \App\Http\Controllers\SaleController::consumeStockForSale($sale);
            } elseif (!$isDispatchedOrDelivered && $hasInventoryTxns && in_array($newStatus, ['cancelled', 'pending'])) {
                \App\Http\Controllers\SaleController::revertStockForSale($sale);
            }

            \App\Models\ActivityLog::create([
                'user_id' => 1,
                'action' => 'steadfast_webhook',
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'description' => "Webhook updated delivery status to: {$newStatus} (Raw: {$status})",
            ]);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Map Steadfast API statuses to our application's delivery_status.
     * Steadfast statuses usually: pending, in_review, active, pickup_completed, in_transit, delivered, cancelled, returned.
     */
    private function mapSteadfastStatus($steadfastStatus)
    {
        switch (strtolower($steadfastStatus)) {
            case 'delivered':
                return 'delivered';
            case 'cancelled':
            case 'returned':
                return 'cancelled';
            case 'in_transit':
            case 'active':
            case 'pickup_completed':
                return 'shipped';
            case 'pending':
            case 'in_review':
                return 'processing';
            default:
                return null;
        }
    }
}
