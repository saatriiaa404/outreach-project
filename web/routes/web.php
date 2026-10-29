<?php

use App\Http\Controllers\ApproveOutreachController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinalizeOutreachController;
use App\Http\Controllers\KifayahController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MustahikController;
use App\Http\Controllers\OutreachAttachmentController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\OutreachPdfController;
use App\Http\Controllers\OutreachSearchController;
use App\Http\Controllers\OutreachVersionController;
use App\Http\Controllers\ReturnOutreachController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\SpecialUnlockController;
use App\Http\Controllers\SpecialUnlockRequestController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('outreach/search', [OutreachSearchController::class, 'store'])
        ->name('outreaches.search');
    Route::delete('outreach/search', [OutreachSearchController::class, 'destroy'])
        ->name('outreaches.search.clear');
    Route::resource('outreach', OutreachController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
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
    Route::post('outreach/{outreach}/finalize', FinalizeOutreachController::class)
        ->name('outreaches.finalize');
    Route::post('outreach/{outreach}/return', ReturnOutreachController::class)
        ->name('outreaches.return');
    Route::post('outreach/{outreach}/approve', ApproveOutreachController::class)
        ->name('outreaches.approve');
    Route::get('outreach/{outreach}/versions/{version}/attachments/{attachment}', [OutreachVersionController::class, 'attachment'])
        ->name('outreaches.versions.attachments.show');
    Route::get('outreach/{outreach}/versions/{version}/signatures/{form}', [OutreachVersionController::class, 'signature'])
        ->whereIn('form', ['mustahik', 'kifayah'])
        ->name('outreaches.versions.signatures.show');
    Route::get('outreach/{outreach}/versions/{version}/pdf/{document}', OutreachPdfController::class)
        ->whereIn('document', ['outreach', 'mustahik', 'kifayah'])
        ->name('outreaches.versions.pdf');
    Route::post('outreach/{outreach}/special-unlock-requests', [SpecialUnlockRequestController::class, 'store'])
        ->name('outreaches.special-unlock-requests.store');
    Route::post('outreach/{outreach}/special-unlock', [SpecialUnlockController::class, 'unlock'])
        ->name('outreaches.special-unlock');
    Route::post('outreach/{outreach}/special-unlock/lock', [SpecialUnlockController::class, 'lock'])
        ->name('outreaches.special-unlock.lock');
    Route::post('outreach/{outreach}/special-unlock/submit', [SpecialUnlockController::class, 'submit'])
        ->name('outreaches.special-unlock.submit');
    Route::get('master-data', [MasterDataController::class, 'index'])
        ->name('master-data.index');
    Route::post('master-data/types', [MasterDataController::class, 'storeType'])
        ->name('master-data.types.store');
    Route::put('master-data/sources/{source}', [MasterDataController::class, 'updateSource'])
        ->name('master-data.sources.update');
    Route::put('master-data/types/{type}', [MasterDataController::class, 'updateType'])
        ->name('master-data.types.update');
    Route::get('users', [UserController::class, 'index'])
        ->name('users.index');
    Route::post('users', [UserController::class, 'store'])
        ->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->name('users.update');
    Route::put('users/{user}/password', [UserController::class, 'resetPassword'])
        ->name('users.password');
    Route::get('archive', [ArchiveController::class, 'index'])
        ->name('archive.index');
    Route::post('archive/outreach/{outreach}', [ArchiveController::class, 'store'])
        ->name('archive.store');
    Route::put('archive/{record}', [ArchiveController::class, 'update'])
        ->name('archive.update');
    Route::post('archive/{record}/verify', [ArchiveController::class, 'verify'])
        ->name('archive.verify');
    Route::get('templates', [TemplateController::class, 'index'])
        ->name('templates.index');
    Route::post('templates/signatures/{kepala}', [TemplateController::class, 'storeSignature'])
        ->name('templates.signatures.store');
    Route::post('templates/signatures/{template}/deactivate', [TemplateController::class, 'deactivateSignature'])
        ->name('templates.signatures.deactivate');
    Route::get('templates/signatures/{template}/image', [TemplateController::class, 'signatureImage'])
        ->name('templates.signatures.image');
    Route::post('templates/stamps', [TemplateController::class, 'storeStamp'])
        ->name('templates.stamps.store');
    Route::post('templates/stamps/{stamp}/deactivate', [TemplateController::class, 'deactivateStamp'])
        ->name('templates.stamps.deactivate');
    Route::get('templates/stamps/{stamp}/image', [TemplateController::class, 'stampImage'])
        ->name('templates.stamps.image');
    Route::get('special-unlock-requests', [SpecialUnlockRequestController::class, 'index'])
        ->name('special-unlock-requests.index');
    Route::post('special-unlock-requests/{specialUnlockRequest}/cancel', [SpecialUnlockRequestController::class, 'cancel'])
        ->name('special-unlock-requests.cancel');
    Route::post('special-unlock-requests/{specialUnlockRequest}/process', [SpecialUnlockRequestController::class, 'process'])
        ->name('special-unlock-requests.process');
    Route::post('special-unlock-requests/{specialUnlockRequest}/reject', [SpecialUnlockRequestController::class, 'reject'])
        ->name('special-unlock-requests.reject');
    Route::put('outreach/{outreach}/autosave', [OutreachController::class, 'autosave'])
        ->name('outreaches.autosave');
});

require __DIR__.'/settings.php';
