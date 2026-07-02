<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupApiController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\SyncApiController;
use App\Http\Controllers\Api\TopicApiController;
use Illuminate\Support\Facades\Route;

// Consumed by the Java desktop client (SDD 3.3 Desktop interface / 6.2-6.5).
// The web app itself uses session auth via Breeze and does not call these.

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/groups', [GroupApiController::class, 'index']);
    Route::post('/groups/{group}/join', [GroupApiController::class, 'join']);
    Route::post('/groups/{group}/rules', [GroupApiController::class, 'decideRules']);
    Route::get('/groups/{group}/topics', [GroupApiController::class, 'topics']);
    Route::get('/groups/{group}/members', [GroupApiController::class, 'members']);
    Route::get('/topics/{topic}', [TopicApiController::class, 'show']);
    Route::post('/posts/{post}/mark-answer', [TopicApiController::class, 'markAnswer']);

    Route::middleware('active.member')->group(function () {
        Route::get('/groups/{group}/messages', [GroupApiController::class, 'messages']);
        Route::post('/groups/{group}/messages', [GroupApiController::class, 'storeMessage']);
        Route::post('/groups/{group}/topics', [TopicApiController::class, 'store']);
        Route::post('/topics/{topic}/posts', [TopicApiController::class, 'storePost']);
    });

    Route::get('/quizzes', [QuizApiController::class, 'index']);
    Route::get('/quizzes/{quiz}', [QuizApiController::class, 'show']);
    Route::post('/quizzes/{quiz}/start', [QuizApiController::class, 'start']);
    Route::post('/quizzes/{quiz}/submit', [QuizApiController::class, 'submit']);
    Route::get('/quizzes/{quiz}/report', [QuizApiController::class, 'report']);

    Route::get('/sync/pull', [SyncApiController::class, 'pull']);
    Route::post('/sync/push', [SyncApiController::class, 'push']);
});
