<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Profile;
use App\Models\UserSkill;
use App\Models\WorkExperience;
use App\Models\Company;
use Illuminate\Http\Request;

class RagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function getAllJobs()
    // {
    //     $data = [];

    //     // Add jobs data
    //     $jobs = Job::select('title', 'description', 'requirements', 'type')->get();
    //     foreach ($jobs as $job) {
    //         $data[] = [
    //             'title' => $job->title,
    //             'description' => $job->description,
    //             'requirements' => $job->requirements,
    //             'type' => $job->type
    //         ];
    //     }

    //     // Add profile data
    //     $profile = auth()->user()->profile()->select('professional_bio', 'years_of_experience', 'location')->first();
    //     if ($profile) {
    //         $data[] = [
    //             'professional_bio' => $profile->professional_bio, 'years_of_experience' => $profile->years_of_experience, 'location' => $profile->location
    //         ];
    //     }

    //     // Add user skills
    //     $skills = [];
    //     $userSkills = auth()->user()->skills()->select('title', 'years_of_experience', 'proficiency_level')->get();
    //     foreach ($userSkills as $skill) {
    //         $skills[] = [
    //             'title' => $skill->title,
    //             'years_of_experience' => $skill->years_of_experience,
    //             'proficiency_level' => $skill->proficiency_level
    //         ];
    //     }
    //     $data[] = ['skills' => $skills];

    //     // Add work experience
    //     $workExperiences = auth()->user()->workExperiences()->select('company_name', 'position', 'description', 'achievements')->get();
    //     foreach ($workExperiences as $experience) {
    //         $data[] = [
    //             'company_name' => $experience->company_name,
    //             'position' => $experience->position,
    //             'description' => $experience->description,
    //             'achievements' => $experience->achievements
    //         ];
    //     }

    //     // Add company data
    //     $company = auth()->user()->company()->select('description')->first();
    //     if ($company) {
    //         $data[] = [
    //             'description' => $company->description
    //         ];
    //     }

    //     return response()->json([
    //         'data' => $data
    //     ]);

    // }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
