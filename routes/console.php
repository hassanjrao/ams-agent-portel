<?php

use App\Services\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:expire-holds', function (BookingService $bookings) {
    $this->info($bookings->expireHolds().' holds expired.');
})->purpose('Mark unpaid holds whose time ran out as expired');

// Seat counts already ignore a hold the moment it runs out; this just updates the status agents see.
// On the server: a cron entry running `php artisan schedule:run` every minute.
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();
