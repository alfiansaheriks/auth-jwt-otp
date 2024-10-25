<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JWTAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\GoogleMapsController;


Route::prefix('auth')->group(function () {
    Route::post('register', [JWTAuthController::class, 'register']);
    Route::post('verify-otp', [JWTAuthController::class, 'verifyOtp']); // New route to verify OTP
    Route::post('login', [JWTAuthController::class, 'login']);

    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('user', [JWTAuthController::class, 'getUser']);
        Route::post('logout', [JWTAuthController::class, 'logout']);
    });
});

Route::prefix('profile')->group(function () {
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::post('add-pin', [ProfileController::class, 'addPin']);
        Route::post('add-name-email', [ProfileController::class, 'addNameandEmail']);
    });
});

Route::prefix('maps')->group(function () {
    Route::get('/directions', [GoogleMapsController::class, 'getDirections']);
});
