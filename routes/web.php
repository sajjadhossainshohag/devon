<?php

use App\Http\Controllers\ValetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ValetController::class, 'dashboard'])->name('dashboard');

// API Routes
Route::prefix('api')->group(function () {
    Route::get('/sites', [ValetController::class, 'getSites']);
    Route::post('/sites/link', [ValetController::class, 'linkSite']);
    Route::delete('/sites/unlink', [ValetController::class, 'unlinkSite']);
    Route::post('/sites/secure', [ValetController::class, 'secureSite']);
    Route::post('/valet/restart', [ValetController::class, 'restartValet']);
    Route::post('/valet/switch-php', [ValetController::class, 'switchPhp']);
});