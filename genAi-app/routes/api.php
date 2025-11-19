<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\RagController;

use App\Http\Controllers\API\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CVAnalysisController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\RealtimeInterviewController;
use App\Http\Controllers\UserSkillController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\UserCvController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::post('/cv/analyze', [CVAnalysisController::class, 'analyze']);
// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/rag/jobs/{profileId?}', [RagController::class, 'getAllJobs']);
Route::get('/rag/alljobs', [RagController::class, 'getJobs']);
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
    // Route::delete('/profile/skills/{skillId}', [ProfileController::class, 'deleteSkill']);
     Route::delete('/profile/skills/{skillId}', [ProfileController::class, 'deleteSkill']);

    // Jobs

    Route::get('/jobs/{job}', [JobController::class, 'show']);
    // Route::post('/jobs/{job}/apply', [JobController::class, 'apply']);
    Route::post('/jobs/{job}/apply', [JobController::class, 'apply']);

    Route::get('/jobs/applications/my', [JobController::class, 'myApplications']);
    Route::get('/jobs/recommended', [JobController::class, 'recommendedJobs']);
 // ✅ CV Routes (PROTECTED)
    Route::post('/user-cvs',                [UserCvController::class, 'store']);
    Route::post('/profile/cv',              [ProfileController::class, 'saveCv']);

    // Admin routes (use middleware class directly to avoid Kernel changes)
    Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'addUser']);
        Route::delete('/users/{userID}', [AdminController::class, 'deleteUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::get('/companies', [AdminController::class, 'companies']);
        Route::get('/jobs', [AdminController::class, 'jobs']);
        Route::post('/jobs', [AdminController::class, 'createJob']);
        Route::put('/jobs/{job}', [AdminController::class, 'updateJob']);
        Route::delete('/jobs/{jobID}', [AdminController::class, 'deleteJob']);
        Route::post('/companies', [AdminController::class, 'createCompany']);
        Route::put('/companies/{company}', [AdminController::class, 'updateCompany']);
        Route::delete('/companies/{companyID}', [AdminController::class, 'deleteCompany']);
        Route::put('/jobs/{job}/status', [AdminController::class, 'updateJobStatus']);
        Route::put('/applications/{application}/status', [AdminController::class, 'updateApplicationStatus']);
    });
});



// User Skills
Route::get('/users/{userId}/skills', [UserSkillController::class, 'index']);
// Route::post('/profile/skills', [UserSkillController::class, 'store']);
Route::put('/profile/skills/{id}', [UserSkillController::class, 'update']);
Route::delete('/profile/skills/{id}', [UserSkillController::class, 'destroy']);



Route::post('/interviews/start', [InterviewController::class, 'start']);

Route::get('/interviews/{id}/next-question', [InterviewController::class, 'nextQuestion']);
Route::post('/interviews/{id}/finalize', [InterviewController::class, 'finalize']);
Route::get('/interviews/{id}/report', [InterviewController::class, 'show']);

// Real-time Interview
Route::post('/interviews/{id}/rt/start', [RealtimeInterviewController::class, 'start']);
Route::post('/interviews/{id}/rt/submit-answer', [RealtimeInterviewController::class, 'submitAnswer']);

// MUST BE LAST — avoid swallowing other routes
Route::get('/interviews/{id}', [InterviewController::class, 'show']);


// CV generation (AI-powered)
Route::post('/cv/generate', [CVController::class, 'generate']);


