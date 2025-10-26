<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Programming Languages
            ['name' => 'PHP', 'category' => 'Programming Languages'],
            ['name' => 'JavaScript', 'category' => 'Programming Languages'],
            ['name' => 'Python', 'category' => 'Programming Languages'],
            ['name' => 'Java', 'category' => 'Programming Languages'],
            ['name' => 'C++', 'category' => 'Programming Languages'],

            // Frameworks
            ['name' => 'Laravel', 'category' => 'Frameworks'],
            ['name' => 'React', 'category' => 'Frameworks'],
            ['name' => 'Vue.js', 'category' => 'Frameworks'],
            ['name' => 'Angular', 'category' => 'Frameworks'],
            ['name' => 'Django', 'category' => 'Frameworks'],

            // Databases
            ['name' => 'MySQL', 'category' => 'Databases'],
            ['name' => 'PostgreSQL', 'category' => 'Databases'],
            ['name' => 'MongoDB', 'category' => 'Databases'],
            ['name' => 'Redis', 'category' => 'Databases'],

            // Tools & Technologies
            ['name' => 'Git', 'category' => 'Tools'],
            ['name' => 'Docker', 'category' => 'Tools'],
            ['name' => 'AWS', 'category' => 'Cloud'],
            ['name' => 'Azure', 'category' => 'Cloud'],
            ['name' => 'Linux', 'category' => 'Operating Systems'],

            // Soft Skills
            ['name' => 'Team Leadership', 'category' => 'Soft Skills'],
            ['name' => 'Project Management', 'category' => 'Soft Skills'],
            ['name' => 'Communication', 'category' => 'Soft Skills'],
            ['name' => 'Problem Solving', 'category' => 'Soft Skills'],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
