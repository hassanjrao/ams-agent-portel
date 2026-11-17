<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The live site has no sign-up for staff: AMS admins are created here. Prints a one-time password.
Artisan::command('portal:create-admin {email} {name}', function (string $email, string $name) {
    if (User::query()->where('email', $email)->exists()) {
        $this->error("A login for {$email} already exists.");

        return 1;
    }

    $password = Str::password(16, symbols: false);
    $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
    $user->role = UserRole::Admin;
    $user->email_verified_at = now();
    $user->save();

    $this->info("Admin {$email} created. Password (shown once, change it after logging in): {$password}");
})->purpose('Create an AMS admin login');

Artisan::command('bookings:expire-holds', function (BookingService $bookings) {
    $this->info($bookings->expireHolds().' holds expired.');
})->purpose('Mark unpaid holds whose time ran out as expired');

// Seat counts already ignore a hold the moment it runs out; this just updates the status agents see.
// On the server: a cron entry running `php artisan schedule:run` every minute.
Schedule::command('bookings:expire-holds')->everyMinute()->withoutOverlapping();

// Shared hosting has no long-running worker: the scheduler drains the queue (portal emails) each minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
