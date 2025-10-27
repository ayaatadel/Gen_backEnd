<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/education', [ProfileController::class, 'addEducation']);
    Route::post('/profile/work-experience', [ProfileController::class, 'addWorkExperience']);
    Route::post('/profile/skills', [ProfileController::class, 'addSkills']);
    Route::delete('/profile/skills/{skillId}', [ProfileController::class, 'deleteSkill']);

    // Jobs
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::post('/jobs/{job}/apply', [JobController::class, 'apply']);
    Route::get('/jobs/applications/my', [JobController::class, 'myApplications']);
    Route::get('/jobs/recommended', [JobController::class, 'recommendedJobs']);

    // Admin routes (use middleware class directly to avoid Kernel changes)
    Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/companies', [AdminController::class, 'companies']);
        Route::get('/jobs', [AdminController::class, 'jobs']);
        Route::post('/jobs', [AdminController::class, 'createJob']);
        Route::delete('/jobs/{jobID}', [AdminController::class, 'deleteJob']);
        Route::post('/companies', [AdminController::class, 'createCompany']);
        Route::delete('/companies/{companyID}', [AdminController::class, 'deleteCompany']);
        Route::put('/jobs/{job}/status', [AdminController::class, 'updateJobStatus']);
        Route::put('/applications/{application}/status', [AdminController::class, 'updateApplicationStatus']);
    });
});
