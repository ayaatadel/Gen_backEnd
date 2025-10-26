<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profile',
            'education',
            'workExperiences',
            'skills',
            'jobApplications',
        ]);

        return response()->json([
            'profile' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'professional_bio' => 'nullable|string',
            'years_of_experience' => 'nullable|integer|min:0',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = $request->user()->profile;

        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($profile->profile_picture) {
                Storage::disk('public')->delete($profile->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $profile->profile_picture = $path;
        }

        $profile->update($request->only([
            'phone_number',
            'location',
            'professional_bio',
            'years_of_experience',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
        ]);
    }

    public function addEducation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'institution' => 'required|string|max:255',
            'degree' => 'required|string|max:255',
            'field_of_study' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grade' => 'nullable|numeric|min:0|max:4',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $education = $request->user()->education()->create($request->all());

        return response()->json([
            'message' => 'Education added successfully',
            'education' => $education,
        ], 201);
    }

    public function addWorkExperience(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_current' => 'nullable|boolean',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'achievements' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $workExperience = $request->user()->workExperiences()->create($request->all());

        return response()->json([
            'message' => 'Work experience added successfully',
            'work_experience' => $workExperience,
        ], 201);
    }

    public function addSkills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skills' => 'required|array',
            'skills.*.id' => 'required|exists:skills,id',
            'skills.*.years_of_experience' => 'required|integer|min:0',
            'skills.*.proficiency_level' => 'required|in:beginner,intermediate,expert',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $skillsData = collect($request->skills)->mapWithKeys(function ($skill) {
            return [$skill['id'] => [
                'years_of_experience' => $skill['years_of_experience'],
                'proficiency_level' => $skill['proficiency_level'],
            ]];
        })->toArray();

        $request->user()->skills()->sync($skillsData, false);

        return response()->json([
            'message' => 'Skills added successfully',
            'skills' => $request->user()->skills,
        ]);
    }
}
