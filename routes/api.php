<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GigApplicationController;
use App\Http\Controllers\GigBookmarkController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\SkillController;
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
});
