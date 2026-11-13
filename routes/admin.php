<?php

use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlightGroupController;
use App\Http\Controllers\Admin\GroupTypeController;
use Illuminate\Support\Facades\Route;

// AMS staff only.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('groups', [FlightGroupController::class, 'index'])->name('groups.index');
    Route::get('groups/create', [FlightGroupController::class, 'create'])->name('groups.create');
    Route::post('groups', [FlightGroupController::class, 'store'])->name('groups.store');
    Route::get('groups/{flightGroup}/edit', [FlightGroupController::class, 'edit'])->name('groups.edit');
    Route::put('groups/{flightGroup}', [FlightGroupController::class, 'update'])->name('groups.update');
    Route::patch('groups/{flightGroup}/status', [FlightGroupController::class, 'updateStatus'])->name('groups.status');

    Route::get('agencies', [AgencyController::class, 'index'])->name('agencies.index');
    Route::get('agencies/{agency}', [AgencyController::class, 'show'])->name('agencies.show');
    Route::patch('agencies/{agency}/status', [AgencyController::class, 'updateStatus'])->name('agencies.status');

    Route::get('airlines', [AirlineController::class, 'index'])->name('airlines.index');
    Route::post('airlines', [AirlineController::class, 'store'])->name('airlines.store');
    Route::put('airlines/{airline}', [AirlineController::class, 'update'])->name('airlines.update');

    Route::get('group-types', [GroupTypeController::class, 'index'])->name('group-types.index');
    Route::post('group-types', [GroupTypeController::class, 'store'])->name('group-types.store');
    Route::put('group-types/{groupType}', [GroupTypeController::class, 'update'])->name('group-types.update');

    Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
});
