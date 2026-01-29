<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->controller(AuthController::class)->group(function () {
    Route::post('token', 'token')->name('token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('revoke', 'revoke')->name('revoke');
        Route::get('me', 'me')->name('me');
    });
});
