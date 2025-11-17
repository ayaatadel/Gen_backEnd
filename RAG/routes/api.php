<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\RagController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Jobs
Route::get('/jobs', [JobController::class, 'index']);

// Public RAG endpoints
Route::get('/rag/jobs/{profileId?}', [RagController::class, 'getAllJobs']);
Route::get('/rag/alljobs', [RagController::class, 'getJobs']);

/*
|--------------------------------------------------------------------------
| Protected API Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    /*
    |--------------------------------------------------------------------------
    | User Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/education', [ProfileController::class, 'addEducation']);
    Route::post('/profile/work-experience', [ProfileController::class, 'addWorkExperience']);
    Route::post('/profile/skills', [ProfileController::class, 'addSkills']);
    Route::delete('/profile/skills/{skillId}', [ProfileController::class, 'deleteSkill']);

    /*
    |--------------------------------------------------------------------------
    | Jobs
    |--------------------------------------------------------------------------
    */
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::post('/jobs/{job}/apply', [JobController::class, 'apply']);
    Route::get('/jobs/applications/my', [JobController::class, 'myApplications']);
    Route::get('/jobs/recommended', [JobController::class, 'recommendedJobs']);

    /*
    |--------------------------------------------------------------------------
    | RAG (Protected)
    |--------------------------------------------------------------------------
    */
    // Recommendations
    Route::get('/rag/recommendations', [RagController::class, 'getRecommendations']);

    // Natural Language Search
    Route::post('/rag/search-jobs', [RagController::class, 'searchJobs']);

    // Profile Embeddings
    Route::get('/rag/profile/embedding-status', [RagController::class, 'getProfileEmbeddingStatus']);
    Route::post('/rag/profile/generate-embedding', [RagController::class, 'generateProfileEmbedding']);

    // Job Embeddings
    Route::post('/rag/jobs/{jobId}/generate-embedding', [RagController::class, 'generateJobEmbedding']);

    // Candidate Recommendations per job
    Route::get('/rag/jobs/{jobId}/candidates', [RagController::class, 'getCandidatesForJob']);

    /*
    |--------------------------------------------------------------------------
    | RAG Admin (Optional — can move inside admin middleware)
    |--------------------------------------------------------------------------
    */
    Route::post('/rag/jobs/generate-all-embeddings', [RagController::class, 'generateAllJobEmbeddings']);
    Route::post('/rag/profiles/generate-all-embeddings', [RagController::class, 'generateAllProfileEmbeddings']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(AdminMiddleware::class)->prefix('admin')->group(function () {
        // Users
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'addUser']);
        Route::delete('/users/{userID}', [AdminController::class, 'deleteUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);

        // Companies
        Route::get('/companies', [AdminController::class, 'companies']);
        Route::post('/companies', [AdminController::class, 'createCompany']);
        Route::delete('/companies/{companyID}', [AdminController::class, 'deleteCompany']);

        // Jobs
        Route::post('/jobs', [AdminController::class, 'createJob']);
        Route::put('/jobs/{job}', [AdminController::class, 'updateJob']);
        Route::delete('/jobs/{jobID}', [AdminController::class, 'deleteJob']);
        Route::put('/jobs/{job}/status', [AdminController::class, 'updateJobStatus']);

        // Applications
        Route::put('/applications/{application}/status', [AdminController::class, 'updateApplicationStatus']);
    });
});
