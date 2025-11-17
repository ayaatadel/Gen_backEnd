<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\UserSkill;
use App\Models\WorkExperience;
use App\Models\Education;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // User 1: Senior Laravel Developer
        $user1 = User::create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Profile::create([
            'user_id' => $user1->id,
            'phone_number' => '+1-555-0101',
            'location' => 'San Francisco, CA',
            'professional_bio' => 'Experienced full-stack developer with 6 years specializing in Laravel and Vue.js. Passionate about building scalable web applications and clean, maintainable code. Strong background in API development, database optimization, and cloud deployment. Love working in agile teams and mentoring junior developers.',
            'years_of_experience' => 6,
        ]);

        UserSkill::create(['user_id' => $user1->id, 'title' => 'Laravel', 'years_of_experience' => 6, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user1->id, 'title' => 'PHP', 'years_of_experience' => 7, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user1->id, 'title' => 'Vue.js', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user1->id, 'title' => 'MySQL', 'years_of_experience' => 6, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user1->id, 'title' => 'Redis', 'years_of_experience' => 3, 'proficiency_level' => 'intermediate']);
        UserSkill::create(['user_id' => $user1->id, 'title' => 'Docker', 'years_of_experience' => 3, 'proficiency_level' => 'intermediate']);

        WorkExperience::create([
            'user_id' => $user1->id,
            'company_name' => 'Tech Solutions Inc',
            'position' => 'Senior Laravel Developer',
            'start_date' => '2020-03-01',
            'end_date' => null,
            'is_current' => true,
            'location' => 'San Francisco, CA',
            'description' => 'Lead developer for company\'s main SaaS platform. Built RESTful APIs, implemented real-time features, and optimized database performance.',
            'achievements' => 'Reduced API response time by 60%, implemented CI/CD pipeline, mentored 3 junior developers.',
        ]);

        WorkExperience::create([
            'user_id' => $user1->id,
            'company_name' => 'WebDev Agency',
            'position' => 'Full Stack Developer',
            'start_date' => '2018-06-01',
            'end_date' => '2020-02-28',
            'is_current' => false,
            'location' => 'Remote',
            'description' => 'Developed custom web applications for various clients using Laravel and Vue.js.',
            'achievements' => 'Delivered 15+ successful projects, maintained 98% client satisfaction rate.',
        ]);

        Education::create([
            'user_id' => $user1->id,
            'institution' => 'University of California',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
            'start_date' => '2014-09-01',
            'end_date' => '2018-05-15',
            'grade' => 3.7,
            'description' => 'Focused on software engineering and web development.',
        ]);

        // User 2: Junior Developer
        $user2 = User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Profile::create([
            'user_id' => $user2->id,
            'phone_number' => '+1-555-0102',
            'location' => 'New York, NY',
            'professional_bio' => 'Enthusiastic junior developer eager to grow my skills in Laravel development. Recently completed a coding bootcamp and built several personal projects. Quick learner with strong fundamentals in PHP, MySQL, and JavaScript. Looking for mentorship and opportunity to contribute to real-world projects.',
            'years_of_experience' => 1,
        ]);

        UserSkill::create(['user_id' => $user2->id, 'title' => 'Laravel', 'years_of_experience' => 1, 'proficiency_level' => 'beginner']);
        UserSkill::create(['user_id' => $user2->id, 'title' => 'PHP', 'years_of_experience' => 1, 'proficiency_level' => 'intermediate']);
        UserSkill::create(['user_id' => $user2->id, 'title' => 'JavaScript', 'years_of_experience' => 2, 'proficiency_level' => 'intermediate']);
        UserSkill::create(['user_id' => $user2->id, 'title' => 'MySQL', 'years_of_experience' => 1, 'proficiency_level' => 'beginner']);
        UserSkill::create(['user_id' => $user2->id, 'title' => 'Git', 'years_of_experience' => 1, 'proficiency_level' => 'intermediate']);

        WorkExperience::create([
            'user_id' => $user2->id,
            'company_name' => 'Freelance',
            'position' => 'Junior Web Developer',
            'start_date' => '2023-06-01',
            'end_date' => null,
            'is_current' => true,
            'location' => 'Remote',
            'description' => 'Building small business websites and learning Laravel through practical projects.',
            'achievements' => 'Completed 5 client projects, learned modern web development practices.',
        ]);

        Education::create([
            'user_id' => $user2->id,
            'institution' => 'Tech Bootcamp Academy',
            'degree' => 'Certificate',
            'field_of_study' => 'Web Development',
            'start_date' => '2023-01-01',
            'end_date' => '2023-04-30',
            'grade' => null,
            'description' => 'Intensive 16-week full-stack web development program.',
        ]);

        // User 3: Backend API Specialist
        $user3 = User::create([
            'name' => 'Michael Chen',
            'email' => 'michael@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Profile::create([
            'user_id' => $user3->id,
            'phone_number' => '+1-555-0103',
            'location' => 'Austin, TX',
            'professional_bio' => 'Backend specialist with 4 years focusing on API development and microservices architecture. Expert in building scalable RESTful APIs, implementing OAuth authentication, and optimizing database queries. Strong advocate for clean code, comprehensive testing, and API documentation.',
            'years_of_experience' => 4,
        ]);

        UserSkill::create(['user_id' => $user3->id, 'title' => 'Laravel', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user3->id, 'title' => 'RESTful APIs', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user3->id, 'title' => 'MySQL', 'years_of_experience' => 5, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user3->id, 'title' => 'PostgreSQL', 'years_of_experience' => 2, 'proficiency_level' => 'intermediate']);
        UserSkill::create(['user_id' => $user3->id, 'title' => 'OAuth/JWT', 'years_of_experience' => 3, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user3->id, 'title' => 'API Documentation', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);

        WorkExperience::create([
            'user_id' => $user3->id,
            'company_name' => 'Mobile Apps Corp',
            'position' => 'API Developer',
            'start_date' => '2021-01-15',
            'end_date' => null,
            'is_current' => true,
            'location' => 'Austin, TX',
            'description' => 'Design and implement RESTful APIs for mobile applications. Handle authentication, data validation, and performance optimization.',
            'achievements' => 'Built APIs serving 100K+ daily requests, implemented rate limiting and caching strategies.',
        ]);

        Education::create([
            'user_id' => $user3->id,
            'institution' => 'Texas State University',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Information Technology',
            'start_date' => '2016-09-01',
            'end_date' => '2020-05-15',
            'grade' => 3.5,
        ]);

        // User 4: Full Stack with DevOps
        $user4 = User::create([
            'name' => 'Emily Rodriguez',
            'email' => 'emily@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Profile::create([
            'user_id' => $user4->id,
            'phone_number' => '+1-555-0104',
            'location' => 'Remote',
            'professional_bio' => 'Full-stack developer with strong DevOps skills. 5 years experience building and deploying Laravel applications on AWS. Comfortable with both development and infrastructure. Experienced in CI/CD pipelines, Docker, and cloud services. Love automating processes and optimizing deployment workflows.',
            'years_of_experience' => 5,
        ]);

        UserSkill::create(['user_id' => $user4->id, 'title' => 'Laravel', 'years_of_experience' => 5, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user4->id, 'title' => 'AWS', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user4->id, 'title' => 'Docker', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user4->id, 'title' => 'CI/CD', 'years_of_experience' => 3, 'proficiency_level' => 'intermediate']);
        UserSkill::create(['user_id' => $user4->id, 'title' => 'Linux', 'years_of_experience' => 5, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user4->id, 'title' => 'React', 'years_of_experience' => 3, 'proficiency_level' => 'intermediate']);

        WorkExperience::create([
            'user_id' => $user4->id,
            'company_name' => 'Cloud Services Ltd',
            'position' => 'Full Stack Developer',
            'start_date' => '2019-08-01',
            'end_date' => null,
            'is_current' => true,
            'location' => 'Remote',
            'description' => 'Build and deploy web applications using Laravel and React. Manage AWS infrastructure and implement DevOps practices.',
            'achievements' => 'Reduced deployment time by 70%, achieved 99.9% uptime, automated testing and deployment.',
        ]);

        Education::create([
            'user_id' => $user4->id,
            'institution' => 'Online University',
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Software Engineering',
            'start_date' => '2015-09-01',
            'end_date' => '2019-05-15',
            'grade' => 3.8,
        ]);

        // User 5: E-commerce Specialist
        $user5 = User::create([
            'name' => 'David Park',
            'email' => 'david@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Profile::create([
            'user_id' => $user5->id,
            'phone_number' => '+1-555-0105',
            'location' => 'Los Angeles, CA',
            'professional_bio' => 'E-commerce specialist with 4 years building online stores and payment systems. Expert in integrating payment gateways, inventory management, and shipping providers. Built custom e-commerce platforms handling millions in transactions. Strong focus on security and PCI compliance.',
            'years_of_experience' => 4,
        ]);

        UserSkill::create(['user_id' => $user5->id, 'title' => 'Laravel', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user5->id, 'title' => 'E-commerce', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user5->id, 'title' => 'Payment Gateways', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user5->id, 'title' => 'MySQL', 'years_of_experience' => 4, 'proficiency_level' => 'expert']);
        UserSkill::create(['user_id' => $user5->id, 'title' => 'Stripe API', 'years_of_experience' => 3, 'proficiency_level' => 'expert']);

        WorkExperience::create([
            'user_id' => $user5->id,
            'company_name' => 'E-Shop Solutions',
            'position' => 'E-commerce Developer',
            'start_date' => '2020-04-01',
            'end_date' => null,
            'is_current' => true,
            'location' => 'Los Angeles, CA',
            'description' => 'Build custom e-commerce platforms for clients. Integrate payment systems, manage inventory, and implement shipping solutions.',
            'achievements' => 'Processed $5M+ in transactions, integrated 10+ payment providers, zero security breaches.',
        ]);

        Education::create([
            'user_id' => $user5->id,
            'institution' => 'UCLA',
            'degree' => 'Bachelor of Arts',
            'field_of_study' => 'Business Information Systems',
            'start_date' => '2016-09-01',
            'end_date' => '2020-05-15',
            'grade' => 3.6,
        ]);

        $this->command->info('Users with profiles seeded successfully!');
    }
}
