<?php

use App\Http\Controllers\KifayahController;
use App\Http\Controllers\MustahikController;
use App\Http\Controllers\OutreachAttachmentController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('outreach', OutreachController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->names('outreaches');
    Route::put('outreach/{outreach}/source-type', [OutreachController::class, 'changeSourceType'])
        ->name('outreaches.source-type');
    Route::post('outreach/{outreach}/attachments', [OutreachAttachmentController::class, 'store'])
        ->name('outreaches.attachments.store');
    Route::get('outreach/{outreach}/attachments/{attachment}', [OutreachAttachmentController::class, 'show'])
        ->name('outreaches.attachments.show');
    Route::delete('outreach/{outreach}/attachments/{attachment}', [OutreachAttachmentController::class, 'destroy'])
        ->name('outreaches.attachments.destroy');
    Route::put('outreach/{outreach}/mustahik', [MustahikController::class, 'update'])
        ->name('outreaches.mustahik.update');
    Route::put('outreach/{outreach}/mustahik/autosave', [MustahikController::class, 'autosave'])
        ->name('outreaches.mustahik.autosave');
    Route::put('outreach/{outreach}/kifayah', [KifayahController::class, 'update'])
        ->name('outreaches.kifayah.update');
    Route::put('outreach/{outreach}/kifayah/autosave', [KifayahController::class, 'autosave'])
        ->name('outreaches.kifayah.autosave');
    Route::post('outreach/{outreach}/signatures/{form}', [SignatureController::class, 'store'])
        ->whereIn('form', ['mustahik', 'kifayah'])
        ->name('outreaches.signatures.store');
    Route::get('outreach/{outreach}/signatures/{form}', [SignatureController::class, 'show'])
        ->whereIn('form', ['mustahik', 'kifayah'])
        ->name('outreaches.signatures.show');
    Route::delete('outreach/{outreach}/signatures/{form}', [SignatureController::class, 'destroy'])
        ->whereIn('form', ['mustahik', 'kifayah'])
        ->name('outreaches.signatures.destroy');
    Route::put('outreach/{outreach}/autosave', [OutreachController::class, 'autosave'])
        ->name('outreaches.autosave');
});

require __DIR__.'/settings.php';
