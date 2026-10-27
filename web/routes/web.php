<?php

use App\Http\Controllers\OutreachController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('outreach', OutreachController::class)
        ->only(['index', 'create', 'store'])
        ->names('outreaches');
});

require __DIR__.'/settings.php';
