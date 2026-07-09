<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Lecturer\ParticipationController;
use App\Http\Controllers\Lecturer\QuizManageController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/notifications/{notification}/read', function (Illuminate\Notifications\DatabaseNotification $notification) {
        abort_unless($notification->notifiable_id === auth()->id(), 403);
        $notification->markAsRead();

        return back();
    })->name('notifications.read');

    // ---- Groups & membership onboarding (requirement #5) ----
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::get('/groups/{group}/rules', [GroupController::class, 'showRules'])->name('groups.rules');
    Route::post('/groups/{group}/rules', [GroupController::class, 'agreeRules'])->name('groups.rules.decide');

    // ---- Discussions (requirements #1, #2, #6) ----
    Route::middleware('active.member')->group(function () {
        Route::get('/groups/{group}/topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/groups/{group}/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::post('/topics/{topic}/posts', [PostController::class, 'store'])->name('posts.store');

        // ---- Group chat + per-message exclusion (requirement #3) ----
        Route::get('/groups/{group}/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/groups/{group}/messages', [MessageController::class, 'store'])->name('messages.store');
    });

    Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
    Route::get('/topics/{topic}/export', [TopicController::class, 'export'])->name('topics.export');
    Route::post('/posts/{post}/mark-answer', [PostController::class, 'markAnswer'])->name('posts.mark-answer');
    Route::get('/posts/{post}/share', [ShareController::class, 'share'])->name('posts.share');

    // ---- Quizzes (requirement #10) ----
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start'])->name('quizzes.start');
    Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
    Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('/quizzes/{quiz}/report', [QuizController::class, 'report'])->name('quizzes.report');

    // ---- Lecturer area (requirements #9, #10) ----
    Route::middleware('role:lecturer,admin')->prefix('lecturer')->name('lecturer.')->group(function () {
        Route::get('/quizzes/create', [QuizManageController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizManageController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/report', [QuizManageController::class, 'report'])->name('quizzes.report');

        Route::get('/participation', [ParticipationController::class, 'index'])->name('participation');
        Route::post('/participation/recompute', [ParticipationController::class, 'recompute'])->name('participation.recompute');
    });

    // ---- Admin area (requirements #4, #7) ----
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/members', [AdminController::class, 'members'])->name('members');
        Route::get('/groups/{group}/stats', [AdminController::class, 'groupStats'])->name('groups.stats');
        Route::get('/groups/{group}/flags', [AdminController::class, 'flags'])->name('groups.flags');

        Route::post('/memberships/{membership}/warn', [AdminController::class, 'warnMembership'])->name('memberships.warn');
        Route::post('/memberships/{membership}/blacklist', [AdminController::class, 'blacklistMembership'])->name('memberships.blacklist');
        Route::post('/memberships/{membership}/reinstate', [AdminController::class, 'reinstateMembership'])->name('memberships.reinstate');

        Route::delete('/posts/{post}', [AdminController::class, 'deletePost'])->name('posts.delete');
        Route::post('/posts/{post}/restore', [AdminController::class, 'restorePost'])->name('posts.restore');

        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    });
});

require __DIR__.'/auth.php';
