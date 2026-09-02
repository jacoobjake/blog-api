<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuthorProfileController as AdminAuthorProfileController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Models\AuthorProfile;
use App\Models\Blog;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::prefix('auth')->name('auth.')->controller(AdminAuthController::class)->group(function () {
        Route::post('token', 'token')->middleware('guest')->name('token');
        Route::post('session', 'session')->middleware('guest')->name('session');
        Route::post('forgot-password', 'forgotPassword')->middleware('guest')->name('forgot-password');
        Route::post('reset-password', 'resetPassword')->middleware('guest')->name('reset-password');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('revoke', 'revoke')->name('revoke');
            Route::post('invalidate', 'invalidateSession')->name('invalidate-session');
        });
    });

    Route::prefix('profile')->name('profile.')->middleware('auth:sanctum')->controller(AdminProfileController::class)->group(function () {
        Route::put('password', 'updatePassword')->name('password.update');
    });

    Route::prefix('authors')->name('authors.')->middleware('auth:sanctum')->controller(AdminAuthorProfileController::class)->group(function () {
        Route::post('/', 'store')->middleware('can:create,'.AuthorProfile::class)->name('store');
        Route::put('me', 'updateMe')->middleware('author.own.update')->name('me.update');
        Route::put('{authorProfile}', 'update')->middleware('can:update,authorProfile')->name('update');
        Route::delete('{authorProfile}', 'destroy')->middleware('can:delete,authorProfile')->name('destroy');
    });

    Route::prefix('blogs')->name('blogs.')->middleware('auth:sanctum')->controller(AdminBlogController::class)->group(function () {
        Route::post('/', 'store')->middleware('can:create,'.Blog::class)->name('store');
        Route::put('{blog:slug}', 'update')->middleware('can:update,blog')->name('update');
        Route::delete('{blog:slug}', 'destroy')->middleware('can:delete,blog')->name('destroy');
    });
});

Route::prefix('assets')->name('assets.')->middleware('auth:sanctum')->controller(AssetController::class)->group(function () {
    Route::post('/', 'store')->middleware('can:create,App\Models\Asset')->name('store');
    Route::delete('{asset:uuid}', 'destroy')->middleware('can:delete,asset')->name('destroy');
});
