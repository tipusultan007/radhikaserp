<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logFile = 'storage/logs/laravel.log';
$lines = file($logFile);

$count = 0;
foreach ($lines as $line) {
    if (strpos($line, 'Steadfast Webhook Received') !== false) {
        // Extract JSON part
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($line, $jsonStart);
            $data = json_decode($jsonStr, true);
            
            if (isset($data['consignment_id']) && isset($data['tracking_message'])) {
                $sale = \App\Models\Sale::where('consignment_id', $data['consignment_id'])->first();
                if ($sale) {
                    $updates = $sale->tracking_updates ?? [];
                    
                    // Check if this specific message already exists to avoid duplicates
                    $exists = false;
                    foreach ($updates as $u) {
                        if ($u['message'] === $data['tracking_message'] && $u['date'] === $data['updated_at']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $updates[] = [
                            'status' => $data['status'] ?? 'tracking_update',
                            'message' => $data['tracking_message'],
                            'date' => $data['updated_at'] ?? now()->toDateTimeString()
                        ];
                        $sale->tracking_updates = $updates;
                        $sale->save();
                        $count++;
                        echo "Added update for {$data['consignment_id']}\n";
                    }
                }
            }
        }
    }
}
echo "Total retroactively added: $count\n";
