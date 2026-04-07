<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\FinancialRecordAttachmentPreviewController;
use App\Http\Controllers\RealizationStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
});

Route::middleware('auth')->prefix('api')->group(function () {
    Route::patch('/realizations/{realization}/status', RealizationStatusController::class)
        ->name('api.realizations.status.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/financial-records/{record}/attachments/{media}/preview', [FinancialRecordAttachmentPreviewController::class, 'preview'])
        ->name('financial-records.attachments.preview');
    Route::get('/financial-records/{record}/attachments/{media}/file', [FinancialRecordAttachmentPreviewController::class, 'file'])
        ->name('financial-records.attachments.file');
});

Route::get('/admin/{path?}', function (?string $path = null) {
    return redirect('/' . $path, 301);
})->where('path', '.*');
