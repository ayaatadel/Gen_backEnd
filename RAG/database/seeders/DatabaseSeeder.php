<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            JobSeeder::class,
            UserSeeder::class,
        ]);

        $this->command->info('✓ All seeders completed successfully!');
        $this->command->info('');
        $this->command->info('Test Credentials:');
        $this->command->info('───────────────────────────────────');
        $this->command->info('Admin:');
        $this->command->info('  Email: admin@example.com');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Test Users:');
        $this->command->info('  john@example.com (Senior Developer - 6 years)');
        $this->command->info('  sarah@example.com (Junior Developer - 1 year)');
        $this->command->info('  michael@example.com (API Specialist - 4 years)');
        $this->command->info('  emily@example.com (Full Stack + DevOps - 5 years)');
        $this->command->info('  david@example.com (E-commerce Specialist - 4 years)');
        $this->command->info('  All passwords: password');
        $this->command->info('───────────────────────────────────');
        $this->command->info('');
        $this->command->info('Database seeded with:');
        $this->command->info('  ✓ 10 Companies');
        $this->command->info('  ✓ 15 Jobs (various types and locations)');
        $this->command->info('  ✓ 6 Users (1 admin + 5 developers with different profiles)');
        $this->command->info('  ✓ User profiles with skills, work experience, and education');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. php artisan embeddings:generate all');
        $this->command->info('  2. Test login with any user above');
        $this->command->info('  3. Call GET /api/rag/recommendations');
        $this->command->info('');
    }
}
