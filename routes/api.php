<?php

use App\Http\Controllers\V1\Issue\CommentController;
use App\Http\Controllers\V1\IssueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
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
