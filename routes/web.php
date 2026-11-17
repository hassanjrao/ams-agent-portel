<?php

use Illuminate\Support\Facades\Route;

// The portal has no public pages: the address goes straight to login, or to the right dashboard.
Route::redirect('/', '/dashboard')->name('home');

require __DIR__.'/settings.php';
require __DIR__.'/agent.php';
require __DIR__.'/admin.php';
