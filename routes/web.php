<?php

use App\Http\Controllers\Auth\SocialiteController;
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

Route::get('/admin/{path?}', function (?string $path = null) {
    return redirect('/' . $path, 301);
})->where('path', '.*');
