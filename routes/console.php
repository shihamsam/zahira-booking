<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remind admins daily about pending bookings due within 2 days.
Schedule::command('bookings:alert-pending')->dailyAt('08:00');

// Auto-cancel pending bookings whose payment deadline has passed, freeing their dates.
Schedule::command('bookings:auto-cancel-overdue')->everyFifteenMinutes()->withoutOverlapping();
