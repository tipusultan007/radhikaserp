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
                $sale->delivery_status = $newStatus;
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
