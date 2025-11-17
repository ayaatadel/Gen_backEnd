<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Job;
use App\Models\Company;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RagTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with profile
        $this->user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $this->user->id,
            'professional_bio' => 'Experienced Laravel developer with 5 years of experience in building web applications.',
            'years_of_experience' => 5,
            'location' => 'Cairo, Egypt'
        ]);

        // Create test skills
        $this->user->skills()->create([
            'title' => 'Laravel',
            'years_of_experience' => 5,
            'proficiency_level' => 'expert'
        ]);

        $this->user->skills()->create([
            'title' => 'PHP',
            'years_of_experience' => 6,
            'proficiency_level' => 'expert'
        ]);

        // Create test job
        $company = Company::factory()->create([
            'name' => 'Tech Corp',
            'description' => 'Leading tech company'
        ]);

        $this->job = Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Senior Laravel Developer',
            'description' => 'We are looking for an experienced Laravel developer to join our team.',
            'requirements' => 'Laravel, PHP, MySQL, 5+ years experience',
            'location' => 'Remote',
            'type' => 'full-time',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_get_job_recommendations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/rag/recommendations?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'count',
                'recommendations' => [
                    '*' => [
                        'job_id',
                        'title',
                        'company',
                        'location',
                        'type',
                        'similarity_score',
                        'match_percentage',
                        'explanation'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_search_jobs_with_natural_language()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/rag/search-jobs', [
                'query' => 'remote Laravel developer position',
                'limit' => 5
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'query',
                'count',
                'jobs'
            ]);
    }

    /** @test */
    public function it_can_generate_profile_embedding()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/rag/profile/generate-embedding');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile embedding generated successfully'
            ]);

        $this->assertNotNull($this->user->profile->fresh()->embedding);
    }

    /** @test */
    public function it_can_get_embedding_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/rag/profile/embedding-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'has_profile',
                'has_embedding',
                'embedding_generated_at',
                'needs_update'
            ]);
    }

    /** @test */
    public function it_can_find_candidates_for_job()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/rag/jobs/{$this->job->id}/candidates?limit=5");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'job',
                'count',
                'candidates'
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_recommendations()
    {
        $response = $this->getJson('/api/rag/recommendations');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_profile_for_recommendations()
    {
        $userWithoutProfile = User::factory()->create();

        $response = $this->actingAs($userWithoutProfile, 'sanctum')
            ->getJson('/api/rag/recommendations');

        $response->assertStatus(400)
            ->assertJson([
                'has_profile' => false
            ]);
    }

    /** @test */
    public function it_validates_search_query()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/rag/search-jobs', [
                'query' => 'ab' // Too short
            ]);

        $response->assertStatus(422);
    }
}
