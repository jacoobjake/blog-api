<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Admin routes go here
    Route::prefix('auth')->name('auth.')->controller(AdminAuthController::class)->group(function () {
        Route::post('token', 'token')->name('token');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('revoke', 'revoke')->name('revoke');
        });
    });

    Route::prefix('blogs')->name('blogs.')->middleware('auth:sanctum')->controller(AdminBlogController::class)->group(function () {
        Route::post('/', 'store')->name('store');
        Route::put('{blog:slug}', 'update')->name('update');
        Route::delete('{blog:slug}', 'destroy')->name('destroy');
    });
});
