<?php

use App\Http\Controllers\V1\Auth\LoginController;
use App\Http\Controllers\V1\Auth\LogoutController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\Issue\CommentController;
use App\Http\Controllers\V1\IssueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', RegisterController::class)
        ->middleware('throttle:6,1')
        ->name('auth.register');

    Route::post('auth/login', LoginController::class)
        ->middleware('throttle:6,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', LogoutController::class)
            ->name('auth.logout');

        Route::apiResource('issues', IssueController::class)
            ->only([
                'index',
                'store',
                'show',
                'update',
            ]);

        Route::post('issues/{issue}/comments', CommentController::class)
            ->name('issues.comments.store');
    });
});
