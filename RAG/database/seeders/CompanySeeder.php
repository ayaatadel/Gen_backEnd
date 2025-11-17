<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'TechCorp Solutions',
                'location' => 'San Francisco, CA',
                'description' => 'Leading technology company specializing in cloud solutions, AI, and machine learning. We build cutting-edge software products used by millions of users worldwide.',
                'website' => 'https://techcorp.com',
                'logo' => null,
            ],
            [
                'name' => 'Digital Innovations Inc',
                'location' => 'New York, NY',
                'description' => 'Fast-growing startup focused on digital transformation and enterprise software solutions. We help businesses modernize their technology stack.',
                'website' => 'https://digitalinnovations.com',
                'logo' => null,
            ],
            [
                'name' => 'WebDev Masters',
                'location' => 'Austin, TX',
                'description' => 'Web development agency specializing in modern web applications, e-commerce platforms, and custom software development using Laravel, React, and Vue.js.',
                'website' => 'https://webdevmasters.com',
                'logo' => null,
            ],
            [
                'name' => 'Cloud Systems Co',
                'location' => 'Seattle, WA',
                'description' => 'Cloud infrastructure and DevOps company providing scalable solutions for enterprises. Expert in AWS, Azure, and Google Cloud Platform.',
                'website' => 'https://cloudsystems.com',
                'logo' => null,
            ],
            [
                'name' => 'Data Analytics Pro',
                'location' => 'Boston, MA',
                'description' => 'Data science and analytics company helping businesses make data-driven decisions through advanced analytics, machine learning, and AI.',
                'website' => 'https://dataanalyticspro.com',
                'logo' => null,
            ],
            [
                'name' => 'Mobile First Studios',
                'location' => 'Los Angeles, CA',
                'description' => 'Mobile app development company creating innovative iOS and Android applications. Specializing in React Native and Flutter development.',
                'website' => 'https://mobilefirststudios.com',
                'logo' => null,
            ],
            [
                'name' => 'FinTech Innovations',
                'location' => 'Chicago, IL',
                'description' => 'Financial technology company building next-generation banking and payment solutions. We use cutting-edge technology to revolutionize finance.',
                'website' => 'https://fintechinnovations.com',
                'logo' => null,
            ],
            [
                'name' => 'E-Commerce Solutions Ltd',
                'location' => 'Remote',
                'description' => 'E-commerce platform provider helping businesses sell online. We build scalable, secure, and user-friendly online stores using modern technologies.',
                'website' => 'https://ecommercesolutions.com',
                'logo' => null,
            ],
            [
                'name' => 'AI Research Labs',
                'location' => 'San Jose, CA',
                'description' => 'Artificial intelligence research and development company working on cutting-edge AI models, natural language processing, and computer vision.',
                'website' => 'https://airesearchlabs.com',
                'logo' => null,
            ],
            [
                'name' => 'Startup Hub Inc',
                'location' => 'Denver, CO',
                'description' => 'Innovation hub and startup incubator helping early-stage companies build their products. We provide technical expertise and resources.',
                'website' => 'https://startuphub.com',
                'logo' => null,
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }

        $this->command->info('Companies seeded successfully!');
    }
}
