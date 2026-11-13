<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Where login lands: AMS staff go straight to the admin side.
    Route::get('dashboard', function (Request $request) {
        return $request->user()->isAdmin()
            ? to_route('admin.dashboard')
            : inertia('Dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
