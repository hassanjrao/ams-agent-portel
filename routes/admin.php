<?php

use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlightGroupController;
use App\Http\Controllers\Admin\GroupTypeController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SupplierController;
use Illuminate\Support\Facades\Route;

// AMS staff only.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('groups', [FlightGroupController::class, 'index'])->name('groups.index');
    Route::get('groups/create', [FlightGroupController::class, 'create'])->name('groups.create');
    Route::post('groups', [FlightGroupController::class, 'store'])->name('groups.store');
    Route::get('groups/{flightGroup}/edit', [FlightGroupController::class, 'edit'])->name('groups.edit');
    Route::put('groups/{flightGroup}', [FlightGroupController::class, 'update'])->name('groups.update');
    Route::delete('groups/{flightGroup}', [FlightGroupController::class, 'destroy'])->name('groups.destroy');
    Route::patch('groups/{flightGroup}/status', [FlightGroupController::class, 'updateStatus'])->name('groups.status');
    Route::patch('groups/{flightGroup}/hold-minutes', [FlightGroupController::class, 'updateHoldMinutes'])->name('groups.hold-minutes');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::put('bookings/{booking}/passengers', [BookingController::class, 'updatePassengers'])->name('bookings.passengers');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('agencies', [AgencyController::class, 'index'])->name('agencies.index');
    Route::get('agencies/{agency}', [AgencyController::class, 'show'])->name('agencies.show');
    Route::patch('agencies/{agency}/status', [AgencyController::class, 'updateStatus'])->name('agencies.status');
    Route::get('agencies/{agency}/ledger', [AgencyController::class, 'ledger'])->name('agencies.ledger');
    Route::post('agencies/{agency}/adjustments', [AgencyController::class, 'adjust'])->name('agencies.adjust');

    Route::get('airlines', [AirlineController::class, 'index'])->name('airlines.index');
    Route::post('airlines', [AirlineController::class, 'store'])->name('airlines.store');
    Route::put('airlines/{airline}', [AirlineController::class, 'update'])->name('airlines.update');

    Route::get('group-types', [GroupTypeController::class, 'index'])->name('group-types.index');
    Route::post('group-types', [GroupTypeController::class, 'store'])->name('group-types.store');
    Route::put('group-types/{groupType}', [GroupTypeController::class, 'update'])->name('group-types.update');

    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

    Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
});
