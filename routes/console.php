<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily payment reminders for customers with due balances
Schedule::command('dues:send-reminders')->dailyAt('09:00');

// Synchronize Steadfast Courier delivery statuses
Schedule::command('steadfast:sync')->everyThirtyMinutes();

