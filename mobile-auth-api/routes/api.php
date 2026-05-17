<?php

use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileSystemTokenController;
use App\Http\Controllers\TemporaryTokenVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('mobile.jwt')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
        Route::post('/token/leave', [MobileSystemTokenController::class, 'leave']);
        Route::post('/token/medical', [MobileSystemTokenController::class, 'medical']);
    });

    Route::post('/verify-token', TemporaryTokenVerificationController::class)
        ->middleware('integration.secret');
});
