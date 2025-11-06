<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Company;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 companies first
        $companies = Company::factory(5)->create();

        // Create 50 jobs, distributed among the companies
        foreach ($companies as $company) {
            Job::factory(10)->create([
                'company_id' => $company->id
            ]);
        }
    }
}