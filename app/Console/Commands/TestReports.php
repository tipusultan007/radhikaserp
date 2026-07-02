<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-reports')]
#[Description('Command description')]
class TestReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $request = \Illuminate\Http\Request::create('/api/admin/reports/sales/daily', 'GET');
        $controller = app()->make(\App\Http\Controllers\Api\AdminApiController::class);
        $response = $controller->dailySales($request);
        $this->info("Daily Sales:");
        $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));
        
        $request2 = \Illuminate\Http\Request::create('/api/admin/reports/inventory/stock-summary', 'GET');
        $response2 = $controller->stockSummary($request2);
        $this->info("\nStock Summary:");
        $this->line(json_encode($response2->getData(), JSON_PRETTY_PRINT));
        
        $request3 = \Illuminate\Http\Request::create('/api/admin/reports/financial/cashbook', 'GET');
        $response3 = $controller->cashbook($request3);
        $this->info("\nCashbook:");
        $this->line(json_encode($response3->getData(), JSON_PRETTY_PRINT));
    }
}
