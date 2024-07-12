<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword');
    Route::get('verify-token/{token}', [AuthController::class, 'checkVerifyToken'])->name('checkVerifyToken');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('changePassword');
    Route::get('submit-email/{token}', [AuthController::class, 'submitEmail'])->name('submitEmail');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
