<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\InventoryTransaction;
use App\Http\Controllers\SaleController;

class SteadfastService
{
    /**
     * Create a new consignment in Steadfast Courier.
     *
     * @param array $data Contains invoice, name, phone, address, amount
     * @return array|null Returns the API response or null on failure.
     */
    public static function createOrder(array $data)
    {
        $baseUrl = config('services.steadfast.url', ' https://portal.packzy.com/api/v1');
        $apiKey = config('services.steadfast.api_key');
        $secretKey = config('services.steadfast.secret_key');

        if (empty($apiKey) || empty($secretKey)) {
            Log::warning('Steadfast Courier API credentials missing.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
                'Secret-Key' => $secretKey,
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}/create_order", [
                'invoice' => $data['invoice'],
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'recipient_address' => $data['recipient_address'] ?? 'N/A',
                'cod_amount' => 0,
                'note' => $data['note'] ?? 'ERP Generated Order',
                'delivery_type' => $data['delivery_type'] ?? 1, // 0 for Home Delivery, 1 for Point Delivery
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Steadfast order created successfully.', ['response' => $responseData]);
                return $responseData;
            } else {
                Log::error('Steadfast API returned an error.', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while calling Steadfast API.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Prepare sale data and dispatch to Steadfast.
     *
     * @param \App\Models\Sale $sale
     * @return bool Returns true if successfully dispatched and updated, false otherwise.
     * @throws \Exception
     */
    public static function dispatchSale(\App\Models\Sale $sale)
    {
        // Prevent re-dispatching
        if ($sale->consignment_id) {
            throw new \Exception('Order already dispatched to Steadfast (Consignment ID exists).');
        }

        // Determine Recipient Details
        // Use shipping address first, fallback to customer data
        $recipientName = 'Unknown';
        $recipientPhone = '00000000000';
        $recipientAddress = 'N/A';

        if (!empty($sale->shipping_address)) {
            // Assume shipping_address might be a simple string or JSON. 
            // If it's a string, we just use customer name/phone and the string as address.
            $recipientName = $sale->customer ? $sale->customer->name : 'Walk-in Customer';
            $recipientPhone = $sale->customer ? $sale->customer->phone : '00000000000';
            $recipientAddress = $sale->shipping_address;
        } elseif ($sale->customer) {
            $recipientName = $sale->customer->name;
            $recipientPhone = $sale->customer->phone;
            $recipientAddress = $sale->customer->address ?? 'N/A';
        }

        // Calculate COD Amount
        // If payment status is paid, cod_amount should be 0. Otherwise it's the due_amount.
        $codAmount = $sale->due_amount > 0 ? (float) $sale->due_amount : 0;

        $data = [
            'invoice' => $sale->invoice_no,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'recipient_address' => $recipientAddress,
            'cod_amount' => $codAmount,
            'note' => $sale->notes ?? 'ERP Generated Order',
            'delivery_type' => $sale->delivery_type ?? 1,
        ];

        $response = self::createOrder($data);

        if ($response && isset($response['consignment'])) {
            $consignmentId = $response['consignment']['consignment_id'] ?? null;
            $trackingCode = $response['consignment']['tracking_code'] ?? null;
            
            if ($consignmentId) {
                $sale->consignment_id = $consignmentId;
                // If tracking code is needed, we could store it, but there isn't a column for it right now based on Sale model fields.
                $sale->save();

                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id() ?? 1,
                    'action' => 'steadfast_dispatch',
                    'reference_type' => \App\Models\Sale::class,
                    'reference_id' => $sale->id,
                    'description' => "Order dispatched to Steadfast Courier. Consignment ID: {$consignmentId}",
                ]);

                return true;
            }
        }

        $errorMessage = 'Failed to dispatch to Steadfast courier. Please check credentials or data format.';

        if (is_array($response) && isset($response['errors'])) {
            $errorDetails = [];
            foreach ($response['errors'] as $field => $messages) {
                $errorDetails[] = is_array($messages) ? implode(' ', $messages) : $messages;
            }
            if (!empty($errorDetails)) {
                $errorMessage = 'Steadfast API Error: ' . implode(' ', $errorDetails);
            }
        } elseif (is_array($response) && isset($response['message'])) {
            $errorMessage = 'Steadfast API Error: ' . $response['message'];
        }

        throw new \Exception($errorMessage);
    }

    /**
     * Fetch status for a given consignment ID from Steadfast.
     *
     * @param string|int $consignmentId
     * @return string|null
     */
    public static function checkStatusByCid($consignmentId)
    {
        $baseUrl = rtrim(trim(config('services.steadfast.url', 'https://portal.packzy.com/api/v1')), '/');
        $apiKey = config('services.steadfast.api_key');
        $secretKey = config('services.steadfast.secret_key');

        if (empty($apiKey) || empty($secretKey) || empty($consignmentId)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $apiKey,
                'Secret-Key' => $secretKey,
            ])->get("{$baseUrl}/status_by_cid/{$consignmentId}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['delivery_status'] ?? null;
            } else {
                Log::warning("Steadfast status_by_cid failed for {$consignmentId}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception checking Steadfast status for CID {$consignmentId}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Map Steadfast API status to ERP delivery_status.
     *
     * @param string $steadfastStatus
     * @return string|null
     */
    public static function mapDeliveryStatus($steadfastStatus)
    {
        switch (strtolower(trim($steadfastStatus))) {
            case 'delivered':
            case 'partial_delivered':
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

    /**
     * Sync delivery status for a specific sale.
     *
     * @param Sale $sale
     * @return bool True if sale status was updated or stock was consumed
     */
    public static function syncSaleStatus(Sale $sale)
    {
        if (!$sale->consignment_id) {
            return false;
        }

        $remoteStatus = self::checkStatusByCid($sale->consignment_id);
        if (!$remoteStatus) {
            return false;
        }

        $newDeliveryStatus = self::mapDeliveryStatus($remoteStatus);
        if (!$newDeliveryStatus) {
            return false;
        }

        $oldStatus = $sale->delivery_status;

        $hasInventoryTxns = InventoryTransaction::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->exists();

        $statusChanged = ($oldStatus !== $newDeliveryStatus);
        $needsStockConsumption = in_array($newDeliveryStatus, ['shipped', 'delivered']) && !$hasInventoryTxns;

        if (!$statusChanged && !$needsStockConsumption) {
            return false;
        }

        $sale->delivery_status = $newDeliveryStatus;

        if ($newDeliveryStatus === 'shipped' && !$sale->dispatched_at) {
            $sale->dispatched_at = now();
            $sale->dispatched_by = $sale->dispatched_by ?? 1;
        }

        if ($newDeliveryStatus === 'delivered') {
            if (!$sale->dispatched_at) {
                $sale->dispatched_at = now();
                $sale->dispatched_by = $sale->dispatched_by ?? 1;
            }
            if (!$sale->delivered_at) {
                $sale->delivered_at = now();
                $sale->delivered_by = $sale->delivered_by ?? 1;
            }
        }

        $updates = $sale->tracking_updates ?? [];
        $updates[] = [
            'status' => $remoteStatus,
            'message' => "Status synced from Steadfast API: {$remoteStatus}",
            'date' => now()->toDateTimeString()
        ];
        $sale->tracking_updates = $updates;

        $sale->save();

        // Handle Stock Consumption or Reversion
        $isDispatchedOrDelivered = in_array($newDeliveryStatus, ['shipped', 'delivered']);

        if ($isDispatchedOrDelivered && !$hasInventoryTxns) {
            SaleController::consumeStockForSale($sale);
        } elseif (!$isDispatchedOrDelivered && $hasInventoryTxns && in_array($newDeliveryStatus, ['cancelled', 'pending'])) {
            SaleController::revertStockForSale($sale);
        }

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id() ?? 1,
            'action' => 'steadfast_sync',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'description' => "Synced with Steadfast API: '{$oldStatus}' -> '{$newDeliveryStatus}' (Remote: '{$remoteStatus}')",
        ]);

        return true;
    }

    /**
     * Synchronize all pending Steadfast consignments.
     *
     * @param bool $all If true, syncs all sales with a consignment ID regardless of current status
     * @return array ['total' => int, 'updated' => int, 'errors' => int]
     */
    public static function syncPendingSales($all = false)
    {
        $query = Sale::whereNotNull('consignment_id');

        if (!$all) {
            $query->where(function ($q) {
                $q->whereNotIn('delivery_status', ['delivered', 'cancelled'])
                  ->orWhereNull('delivery_status')
                  ->orWhere(function ($sub) {
                      $sub->where('delivery_status', 'delivered')
                          ->whereDoesntHave('inventoryTransactions');
                  });
            });
        }

        $sales = $query->get();
        $updated = 0;
        $errors = 0;

        foreach ($sales as $sale) {
            try {
                if (self::syncSaleStatus($sale)) {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("Failed to sync sale #{$sale->invoice_no} (CID: {$sale->consignment_id}): " . $e->getMessage());
            }
        }

        return [
            'total' => $sales->count(),
            'updated' => $updated,
            'errors' => $errors
        ];
    }
}
