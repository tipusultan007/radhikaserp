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
        $baseUrl = config('services.steadfast.url', 'https://portal.steadfast.com.bd/api/v1');
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
                'cod_amount' => $data['cod_amount'],
                'note' => $data['note'] ?? 'ERP Generated Order',
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
}
