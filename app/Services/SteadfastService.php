<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        throw new \Exception('Failed to dispatch to Steadfast courier. Please check credentials or data format.');
    }
}
