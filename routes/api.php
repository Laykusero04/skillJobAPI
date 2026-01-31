<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GigApplicationController;
use App\Http\Controllers\GigBookmarkController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\UserSkillController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum', 'check.token.expiry'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    // Verification routes (manual for testing)
    Route::get('/verification/status', [VerificationController::class, 'status']);
    Route::post('/verification/email/verify', [VerificationController::class, 'verifyEmail']);
    Route::post('/verification/phone/verify', [VerificationController::class, 'verifyPhone']);

    // Skill CRUD routes
    Route::apiResource('skills', SkillController::class);

    // Gig CRUD routes
    Route::apiResource('gigs', GigController::class);
    Route::patch('/gigs/{gig}/close', [GigController::class, 'close']);
    Route::patch('/gigs/{gig}/workers', [GigController::class, 'updateWorkers']);

    // Gig Applications
    Route::get('/gigs/{gig}/applications', [GigApplicationController::class, 'index'])
        ->middleware('ensure.employer');
    Route::post('/gigs/{gig}/applications', [GigApplicationController::class, 'store'])
        ->middleware('ensure.freelancer');
    Route::patch('/gigs/{gig}/applications/{application}/status', [GigApplicationController::class, 'updateStatus'])
        ->middleware('ensure.employer');

    // Gig Bookmarks
    Route::get('/bookmarks/gigs', [GigBookmarkController::class, 'index'])
        ->middleware('ensure.freelancer');
    Route::post('/gigs/{gig}/bookmark', [GigBookmarkController::class, 'toggle'])
        ->middleware('ensure.freelancer');

    // Freelancer Skills
    Route::get('/my-skills', [UserSkillController::class, 'index'])
        ->middleware('ensure.freelancer');
    Route::put('/my-skills', [UserSkillController::class, 'update'])
        ->middleware('ensure.freelancer');

    // Freelancer Profile
    Route::get('/freelancer-profile', [FreelancerProfileController::class, 'show'])
        ->middleware('ensure.freelancer');
    Route::patch('/freelancer-profile', [FreelancerProfileController::class, 'update'])
        ->middleware('ensure.freelancer');
    Route::post('/freelancer-profile/resume', [FreelancerProfileController::class, 'uploadResume'])
        ->middleware('ensure.freelancer');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
