<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileEmbeddingService
{
    protected $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Prepare user profile text for embedding
     *
     * @param User $user
     * @return string
     */
    public function prepareProfileText(User $user): string
    {
        $profile = $user->profile;
        $text = "";

        // Basic profile info
        if ($profile) {
            $text .= "Professional Bio: {$profile->professional_bio}\n";
            $text .= "Years of Experience: {$profile->years_of_experience}\n";
            $text .= "Location: {$profile->location}\n";
        }

        // Skills
        $skills = $user->skills;
        if ($skills->count() > 0) {
            $text .= "\nSkills:\n";
            foreach ($skills as $skill) {
                $text .= "- {$skill->title} ({$skill->proficiency_level}, {$skill->years_of_experience} years)\n";
            }
        }

        // Work Experience
        $workExperiences = $user->workExperiences;
        if ($workExperiences->count() > 0) {
            $text .= "\nWork Experience:\n";
            foreach ($workExperiences as $experience) {
                $text .= "- {$experience->position} at {$experience->company_name}\n";
                $text .= "  Location: {$experience->location}\n";
                $text .= "  Description: {$experience->description}\n";
                if ($experience->achievements) {
                    $text .= "  Achievements: {$experience->achievements}\n";
                }
            }
        }

        // Education
        $education = $user->education;
        if ($education->count() > 0) {
            $text .= "\nEducation:\n";
            foreach ($education as $edu) {
                $text .= "- {$edu->degree} in {$edu->field_of_study} from {$edu->institution}\n";
                if ($edu->description) {
                    $text .= "  {$edu->description}\n";
                }
            }
        }

        return $text;
    }

    /**
     * Generate and store embedding for a user profile
     *
     * @param User $user
     * @return bool
     */
    public function generateEmbedding(User $user): bool
    {
        try {
            $profile = $user->profile;
            if (!$profile) {
                Log::warning("User {$user->id} has no profile");
                return false;
            }

            $text = $this->prepareProfileText($user);
            
            if (empty(trim($text))) {
                Log::warning("User {$user->id} has no profile data to embed");
                return false;
            }

            $embedding = $this->embeddingService->generateEmbedding($text);

            if (!$embedding) {
                return false;
            }

            $profile->embedding = json_encode($embedding);
            $profile->embedding_text = $text;
            $profile->embedding_generated_at = now();
            $profile->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Error generating profile embedding: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate embeddings for all profiles that don't have one
     *
     * @return int Number of profiles processed
     */
    public function generateAllEmbeddings(): int
    {
        $users = User::with(['profile', 'skills', 'workExperiences', 'education'])
            ->whereHas('profile', function ($query) {
                $query->whereNull('embedding')
                    ->orWhere('embedding_generated_at', '<', now()->subDays(30));
            })
            ->get();

        $count = 0;
        foreach ($users as $user) {
            if ($this->generateEmbedding($user)) {
                $count++;
            }
            
            // Rate limiting
            sleep(1);
        }

        return $count;
    }

    /**
     * Update embedding when profile or related data is modified
     *
     * @param User $user
     * @return bool
     */
    public function updateEmbedding(User $user): bool
    {
        return $this->generateEmbedding($user);
    }
}
