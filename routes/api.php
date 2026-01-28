<?php

use App\Http\Controllers\AuthController;
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
});
