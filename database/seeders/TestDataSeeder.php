<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Ideation;
use App\Models\Feasibility;
use App\Models\Role;
use App\Models\ProjectHistory;
use App\Models\Skill;
use App\Enums\SkillLevel;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TestDataSeeder extends Seeder
{
    // Stop the model events from firing to prevent side effects so we can seed exactly what we want
    use WithoutModelEvents;

    public function run(): void
    {
        // Create some default roles
        $roles = [
            [
                'name' => 'CoSE',
                'description' => 'College of Science and Engineering team',
                'is_active' => true,
            ],
            [
                'name' => 'Ai Research Role',
                'description' => 'Ai Research Role team',
                'is_active' => true,
            ],
            [
                'name' => 'Chemistry',
                'description' => 'Chemistry Department Staff',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Create additional random roles for testing
        Role::factory(10)->create();

        // Create skills
        $skills = [
            [
                'name' => 'Python',
                'description' => 'Python is a versatile programming language used for web development, data analysis, artificial intelligence, and more.',
                'skill_category' => 'Programming Languages',
            ],
            [
                'name' => 'Java',
                'description' => 'Java is a versatile programming language used for web development, data analysis, artificial intelligence, and more.',
                'skill_category' => 'Programming Languages',
            ],
            [
                'name' => 'C++',
                'description' => 'C++ is a versatile programming language used for web development, data analysis, artificial intelligence, and more.',
                'skill_category' => 'Programming Languages',
            ],
            [
                'name' => 'JavaScript',
                'description' => 'JavaScript is a programming language that enables interactive web pages and is an essential part of web applications.',
                'skill_category' => 'Programming Languages',
            ],
            [
                'name' => 'PHP',
                'description' => 'PHP is a popular general-purpose scripting language that is especially suited to web development.',
                'skill_category' => 'Programming Languages',
            ],
            [
                'name' => 'Laravel',
                'description' => 'Laravel is a web application framework with expressive, elegant syntax for building web applications.',
                'skill_category' => 'Frameworks',
            ],
            [
                'name' => 'React',
                'description' => 'React is a JavaScript library for building user interfaces, particularly web applications.',
                'skill_category' => 'Frameworks',
            ],
            [
                'name' => 'Vue.js',
                'description' => 'Vue.js is a progressive JavaScript framework for building user interfaces.',
                'skill_category' => 'Frameworks',
            ],
            [
                'name' => 'MySQL',
                'description' => 'MySQL is an open-source relational database management system.',
                'skill_category' => 'Databases',
            ],
            [
                'name' => 'PostgreSQL',
                'description' => 'PostgreSQL is a powerful, open source object-relational database system.',
                'skill_category' => 'Databases',
            ],
        ];

        foreach ($skills as $skillData) {
            Skill::create($skillData);
        }

        $admin = User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        $users = User::factory()->count(10)->create();

        // Assign random roles to users
        $allRoles = Role::all();
        foreach ($users as $user) {
            // Assign 1-3 random roles to each user
            $randomRoles = $allRoles->random(rand(1, min(3, $allRoles->count())));
            $user->roles()->attach($randomRoles->pluck('id'));
        }

        // Assign skills to users (except admin)
        $allSkills = Skill::all();
        $skillLevels = SkillLevel::cases();

        foreach ($users as $user) {
            // Assign 3-8 random skills to each user with random skill levels
            $randomSkills = $allSkills->random(rand(3, min(8, $allSkills->count())));
            foreach ($randomSkills as $skill) {
                $skillLevel = $skillLevels[array_rand($skillLevels)];
                $user->skills()->attach($skill->id, ['skill_level' => $skillLevel->value]);
            }
        }

        foreach (User::all() as $user) {
            foreach (range(1, 10) as $i) {
                $project = Project::factory()->create([
                    'user_id' => $user->id,
                    'updated_at' => now()->subDays(rand(1, 100)),
                ]);
                $stage = rand(0, count(config('projman.subforms')) - 1);
                foreach (config('projman.subforms') as $index => $formName) {
                    // Create base data for each form type
                    $formData = [
                        'project_id' => $project->id,
                        'created_at' => $index <= $stage ? now()->subDays(rand(1, 100)) : now(),
                    ];

                    // Add user ID fields based on form type
                    if ($formName === 'App\Models\Feasibility') {
                        $formData['assessed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Scoping') {
                        $formData['assessed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\DetailedDesign') {
                        $formData['designed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Development') {
                        $formData['lead_developer'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Testing') {
                        $formData['test_lead'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Deployed') {
                        $formData['deployed_by'] = $users->random()->id;
                    } elseif ($formName === 'App\Models\Scheduling') {
                        $formData['assigned_to'] = $users->random()->id;
                    }

                    $formName::factory()->create($formData);
                }

                foreach (range(1, rand(1, 100)) as $j) {
                    ProjectHistory::factory()->create([
                        'project_id' => $project->id,
                        'user_id' => $users->random()->id,
                        'created_at' => now()->subDays(rand(1, 100)),
                    ]);
                }
            }
        }
    }
}
