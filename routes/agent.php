<?php

use App\Http\Controllers\Agent\BookingController;
use App\Http\Controllers\Agent\DashboardController;
use App\Http\Controllers\Agent\GroupController;
use App\Http\Controllers\Agent\LedgerController;
use App\Http\Controllers\Agent\PaymentController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

// Travel agencies. Admins who land here are sent to the admin dashboard.
Route::middleware(['auth', 'agent'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('groups/{flightGroup}', [GroupController::class, 'show'])->name('groups.show');
    Route::post('groups/{flightGroup}/bookings', [BookingController::class, 'store'])->name('bookings.store');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::put('bookings/{booking}/passengers', [BookingController::class, 'updatePassengers'])->name('bookings.passengers');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');

    Route::get('ledger', LedgerController::class)->name('ledger');
});

// A payment's receipt, for the agency that sent it and for AMS staff.
Route::middleware(['auth'])->get('payments/{payment}/receipt', ReceiptController::class)->name('payments.receipt');
