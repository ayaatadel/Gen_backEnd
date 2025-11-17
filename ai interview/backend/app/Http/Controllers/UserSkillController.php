<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserSkillController extends Controller
{
    /**
     * GET /api/users/{userId}/skills
     * Get all skills for a user
     */
    public function index(int $userId)
    {
        $skills = DB::table('user_skills')
            ->where('user_id', $userId)
            ->orderBy('proficiency_level', 'desc')
            ->orderBy('years_of_experience', 'desc')
            ->get()
            ->map(function ($skill) {
                // Ensure consistent data format
                return [
                    'id' => (int) $skill->id,
                    'user_id' => (int) $skill->user_id,
                    'title' => $skill->title,
                    'years_of_experience' => (int) $skill->years_of_experience,
                    'proficiency_level' => $skill->proficiency_level,
                    'created_at' => $skill->created_at,
                    'updated_at' => $skill->updated_at,
                ];
            });

        return response()->json(['skills' => $skills]);
    }

    /**
     * POST /api/profile/skills
     * Add a new skill for the user
     * ✅ FIXED: Works with single skill object and user_id in request
     */
    public function store(Request $request)
    {
        // Validate incoming single skill
        $request->validate([
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'proficiency_level' => 'required|string|in:beginner,intermediate,advanced,expert',
        ]);

        $userId = $request->input('user_id');
        $title = $request->input('title');
        $yearsOfExperience = $request->input('years_of_experience');
        $proficiencyLevel = $request->input('proficiency_level');

        // Check if skill with same title already exists for this user
        $existingSkill = DB::table('user_skills')
            ->where('user_id', $userId)
            ->where('title', $title)
            ->first();

        if ($existingSkill) {
            // Update existing skill instead of creating duplicate
            DB::table('user_skills')
                ->where('id', $existingSkill->id)
                ->update([
                    'years_of_experience' => $yearsOfExperience,
                    'proficiency_level' => $proficiencyLevel,
                    'updated_at' => now(),
                ]);
            
            $skill = DB::table('user_skills')->find($existingSkill->id);
            $message = 'Skill updated successfully';
        } else {
            // Insert new skill
            $skillId = DB::table('user_skills')->insertGetId([
                'user_id' => $userId,
                'title' => $title,
                'years_of_experience' => $yearsOfExperience,
                'proficiency_level' => $proficiencyLevel,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $skill = DB::table('user_skills')->find($skillId);
            $message = 'Skill added successfully';
        }

        // Return consistent data format
        return response()->json([
            'message' => $message,
            'skill' => [
                'id' => (int) $skill->id,
                'user_id' => (int) $skill->user_id,
                'title' => $skill->title,
                'years_of_experience' => (int) $skill->years_of_experience,
                'proficiency_level' => $skill->proficiency_level,
                'created_at' => $skill->created_at,
                'updated_at' => $skill->updated_at,
            ]
        ], 201);
    }

    /**
     * PUT /api/profile/skills/{id}
     * Update an existing skill
     */
    public function update(int $id, Request $request)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'years_of_experience' => 'sometimes|required|integer|min:0|max:50',
            'proficiency_level' => 'sometimes|required|string|in:beginner,intermediate,advanced,expert',
        ]);

        $skill = DB::table('user_skills')->find($id);
        if (!$skill) {
            return response()->json(['error' => 'Skill not found'], 404);
        }

        $updateData = [];
        if ($request->has('title')) {
            $updateData['title'] = $request->input('title');
        }
        if ($request->has('years_of_experience')) {
            $updateData['years_of_experience'] = $request->input('years_of_experience');
        }
        if ($request->has('proficiency_level')) {
            $updateData['proficiency_level'] = $request->input('proficiency_level');
        }
        $updateData['updated_at'] = now();

        DB::table('user_skills')->where('id', $id)->update($updateData);

        $updatedSkill = DB::table('user_skills')->find($id);

        return response()->json([
            'message' => 'Skill updated successfully',
            'skill' => [
                'id' => (int) $updatedSkill->id,
                'user_id' => (int) $updatedSkill->user_id,
                'title' => $updatedSkill->title,
                'years_of_experience' => (int) $updatedSkill->years_of_experience,
                'proficiency_level' => $updatedSkill->proficiency_level,
                'created_at' => $updatedSkill->created_at,
                'updated_at' => $updatedSkill->updated_at,
            ]
        ]);
    }

    /**
     * DELETE /api/profile/skills/{id}
     * Delete a skill
     */
    public function destroy(int $id)
    {
        $skill = DB::table('user_skills')->find($id);
        if (!$skill) {
            return response()->json(['error' => 'Skill not found'], 404);
        }

        DB::table('user_skills')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Skill deleted successfully'
        ]);
    }
}