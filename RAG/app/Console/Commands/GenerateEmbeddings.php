<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobEmbeddingService;
use App\Services\ProfileEmbeddingService;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate {type=all : Type of embeddings to generate (jobs, profiles, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for jobs and/or profiles';

    protected $jobEmbeddingService;
    protected $profileEmbeddingService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        JobEmbeddingService $jobEmbeddingService,
        ProfileEmbeddingService $profileEmbeddingService
    ) {
        parent::__construct();
        $this->jobEmbeddingService = $jobEmbeddingService;
        $this->profileEmbeddingService = $profileEmbeddingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        if ($type === 'jobs' || $type === 'all') {
            $this->info('Generating embeddings for jobs...');
            $count = $this->jobEmbeddingService->generateAllEmbeddings();
            $this->info("✓ Generated embeddings for {$count} jobs");
        }

        if ($type === 'profiles' || $type === 'all') {
            $this->info('Generating embeddings for profiles...');
            $count = $this->profileEmbeddingService->generateAllEmbeddings();
            $this->info("✓ Generated embeddings for {$count} profiles");
        }

        if (!in_array($type, ['jobs', 'profiles', 'all'])) {
            $this->error('Invalid type. Use: jobs, profiles, or all');
            return 1;
        }

        $this->info('✓ All embeddings generated successfully!');
        return 0;
    }
}
