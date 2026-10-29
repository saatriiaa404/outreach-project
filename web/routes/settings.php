<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

// Users may change their own password and optional profile (D-067); the name,
// email and self-deletion stay with the ADMIN (R9.1, D-014).
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('users/{user}/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('users/{user}/photo', [ProfileController::class, 'photo'])->name('profile.photo');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
