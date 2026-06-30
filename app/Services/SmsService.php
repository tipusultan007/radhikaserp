<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS using the configured Bangladeshi bulk SMS provider.
     *
     * @param string $to The recipient's phone number
     * @param string $message The text message to send
     * @return bool True if successful, False otherwise
     */
    public static function sendSms(string $to, string $message): bool
    {
        $apiUrl = config('services.bulksms.url');
        $apiKey = config('services.bulksms.key');
        $senderId = config('services.bulksms.sender_id');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning('Bulk SMS configuration is missing. SMS not sent.', ['to' => $to, 'message' => $message]);
            return false;
        }

        try {
            // Most Bangladeshi bulk SMS providers use a GET request format like:
            // http://api.bulksms.com/api.php?api_key=KEY&senderid=SENDER&number=NUM&message=MSG
            // Some use POST. This implementation uses a generic GET structure that covers many providers.
            // Adjust the query parameters as needed based on your specific provider's documentation.

            $response = Http::get($apiUrl, [
                'api_key' => $apiKey,
                'senderid' => $senderId,
                'number' => $to,
                'message' => $message,
                // Some providers use 'contacts' instead of 'number', or 'msg' instead of 'message'.
                // 'contacts' => $to,
                // 'msg' => $message,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully.', ['to' => $to, 'response' => $response->body()]);
                return true;
            } else {
                Log::error('Failed to send SMS.', ['to' => $to, 'status' => $response->status(), 'response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending SMS.', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
