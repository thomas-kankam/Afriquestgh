<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Client\ClientAuthenticationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('client')->group(function () {
    Route::post('login', [ClientAuthenticationController::class, 'login']);
    Route::post('register', [ClientAuthenticationController::class, 'register']);
    Route::post('verify-otp', [ClientAuthenticationController::class, 'verifyOtp']);
    Route::post('resend-otp', [ClientAuthenticationController::class, 'resendOtp']);

    Route::middleware('auth:client-api')->group(function () {
        Route::post('logout', [ClientAuthenticationController::class, 'logout']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthenticationController::class, 'login']);
    Route::post('register', [AdminAuthenticationController::class, 'register']);
    Route::post('verify-otp', [AdminAuthenticationController::class, 'verifyOtp']);
    Route::post('resend-otp', [AdminAuthenticationController::class, 'resendOtp']);

    Route::middleware('auth:admin-api')->group(function () {
        Route::post('logout', [AdminAuthenticationController::class, 'logout']);
    });
});
