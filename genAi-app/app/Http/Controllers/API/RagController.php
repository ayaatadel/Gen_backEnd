<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Profile;
use App\Models\UserSkill;
use App\Models\WorkExperience;
use App\Models\Company;
use Illuminate\Http\Request;
use OpenAI\Client;
use GuzzleHttp\Client as GuzzleClient;

class RagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllJobs(Request $request, $profileId = null)
    {
        // If no profile ID is provided, try to get it from the query parameter
        if (!$profileId) {
            $profileId = $request->query('profile_id');
        }

        // Check if profile exists
        if ($profileId) {
            $profile = Profile::find($profileId);
            if (!$profile) {
                return response()->json([
                    'message' => 'Profile not found'
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Profile ID is required'
            ], 400);
        }

        $data = [];

        // Add jobs data
        // $jobs = Job::select('title', 'description', 'requirements', 'type')->get();
        // foreach ($jobs as $job) {
        //     $data[] = [
        //         'title' => $job->title,
        //         'description' => $job->description,
        //         'requirements' => $job->requirements,
        //         'type' => $job->type
        //     ];
        // }

        // Add profile data
        $data[] = [
            'professional_bio' => $profile->professional_bio,
            'years_of_experience' => $profile->years_of_experience
        ];

        // Add user skills
        $skills = [];
        $userSkills = $profile->user->skills()->select('title', 'years_of_experience', 'proficiency_level')->get();
        foreach ($userSkills as $skill) {
            $skills[] = [
                'title' => $skill->title,
                // 'years_of_experience' => $skill->years_of_experience,
                'proficiency_level' => $skill->proficiency_level
            ];
        }
        $data[] = ['skills' => $skills];

        // Add work experience
        $workExperiences = $profile->user->workExperiences()->select('company_name', 'position', 'description', 'achievements')->get();
        foreach ($workExperiences as $experience) {
            $data[] = [
                'company_name' => $experience->company_name,
                'position' => $experience->position,
                'description' => $experience->description,
                'achievements' => $experience->achievements
            ];
        }

        // Add company data
        $company = $profile->user->company()->select('description')->first();
        if ($company) {
            $data[] = [
                'description' => $company->description
            ];
        }}

    //     return response()->json([
    //         'data' => $data
    //     ]);

    // }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     //
    // }

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

    /**
     * Split array into chunks of specified size
     *
     * @param array $array Original array to be chunked
     * @param int $chunkSize Size of each chunk
     * @return array Array of chunks
     */
    private function chunkArray(array $array, int $chunkSize = 2): array
    {
        return array_chunk($array, $chunkSize);
    }

    /**
     * Get all jobs with selected fields and optionally chunk them
     */
    private function getEmbeddings($text)
    {
        $client = new GuzzleClient();

        $response = $client->post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'input' => $text,
                'model' => 'text-embedding-ada-002',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['data'][0]['embedding'];
    }

    private function prepareJobText($job)
    {
        return "Title: {$job['title']}\n" .
               "Description: {$job['description']}\n" .
               "Requirements: {$job['requirements']}\n" .
               "Type: {$job['type']}";
    }

    public function getJobs(Request $request)
    {
        $jobs = Job::select('title', 'description', 'requirements', 'type')->get()->toArray();

        // Get chunk size from query parameter, default to 2 if not provided
        $chunkSize = $request->query('chunk_size', 2);

        // Convert chunk size to integer
        $chunkSize = (int) $chunkSize;

        // Ensure chunk size is at least 1
        $chunkSize = max(1, $chunkSize);

        // Chunk the jobs array if requested
        $chunkedJobs = $this->chunkArray($jobs, $chunkSize);

        // Format chunks and get embeddings
        $formattedChunks = [];
        foreach ($chunkedJobs as $index => $chunk) {
            $chunkJobs = array_map(function($job) {
                $jobText = $this->prepareJobText($job);
                return [
                    'title' => $job['title'],
                    'description' => substr($job['description'], 0, 100) . '...', // Truncate for display
                    'requirements' => substr($job['requirements'], 0, 100) . '...', // Truncate for display
                    'type' => $job['type'],
                    'full_text' => $jobText,
                    'embedding' => $this->getEmbeddings($jobText)
                ];
            }, $chunk);

            $formattedChunks[] = [
                'jobs' => $chunkJobs
            ];
        }

        return response()->json([
            'status' => 'success',
            'total_jobs' => count($jobs),
            'chunk_size' => $chunkSize,
            'number_of_chunks' => count($chunkedJobs),
            'chunks' => $formattedChunks
        ]);
    }
}
