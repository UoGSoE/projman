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
            // Programming Languages
            [
                'name' => 'PHP',
                'description' => 'Server-side scripting language for web development',
                'skill_category' => 'Programming Languages'
            ],
            [
                'name' => 'JavaScript',
                'description' => 'Client-side and server-side programming language',
                'skill_category' => 'Programming Languages'
            ],
            [
                'name' => 'Python',
                'description' => 'High-level programming language for various applications',
                'skill_category' => 'Programming Languages'
            ],
            [
                'name' => 'Java',
                'description' => 'Object-oriented programming language',
                'skill_category' => 'Programming Languages'
            ],
            [
                'name' => 'C#',
                'description' => 'Microsoft programming language for .NET applications',
                'skill_category' => 'Programming Languages'
            ],

            // Frameworks
            [
                'name' => 'Laravel',
                'description' => 'PHP web application framework',
                'skill_category' => 'Frameworks'
            ],
            [
                'name' => 'React',
                'description' => 'JavaScript library for building user interfaces',
                'skill_category' => 'Frameworks'
            ],
            [
                'name' => 'Vue.js',
                'description' => 'Progressive JavaScript framework',
                'skill_category' => 'Frameworks'
            ],
            [
                'name' => 'Angular',
                'description' => 'TypeScript-based web application framework',
                'skill_category' => 'Frameworks'
            ],
            [
                'name' => 'Django',
                'description' => 'Python web framework',
                'skill_category' => 'Frameworks'
            ],

            // Databases
            [
                'name' => 'MySQL',
                'description' => 'Relational database management system',
                'skill_category' => 'Databases'
            ],
            [
                'name' => 'PostgreSQL',
                'description' => 'Advanced open-source relational database',
                'skill_category' => 'Databases'
            ],
            [
                'name' => 'MongoDB',
                'description' => 'NoSQL document database',
                'skill_category' => 'Databases'
            ],
            [
                'name' => 'Redis',
                'description' => 'In-memory data structure store',
                'skill_category' => 'Databases'
            ],

            // DevOps
            [
                'name' => 'Docker',
                'description' => 'Containerization platform',
                'skill_category' => 'DevOps'
            ],
            [
                'name' => 'Kubernetes',
                'description' => 'Container orchestration platform',
                'skill_category' => 'DevOps'
            ],
            [
                'name' => 'AWS',
                'description' => 'Amazon Web Services cloud platform',
                'skill_category' => 'DevOps'
            ],
            [
                'name' => 'Git',
                'description' => 'Version control system',
                'skill_category' => 'DevOps'
            ],

            // Design
            [
                'name' => 'UI/UX Design',
                'description' => 'User interface and user experience design',
                'skill_category' => 'Design'
            ],
            [
                'name' => 'Figma',
                'description' => 'Collaborative interface design tool',
                'skill_category' => 'Design'
            ],
            [
                'name' => 'Adobe Creative Suite',
                'description' => 'Creative software suite for design and multimedia',
                'skill_category' => 'Design'
            ],

            // Project Management
            [
                'name' => 'Agile',
                'description' => 'Iterative approach to project management',
                'skill_category' => 'Project Management'
            ],
            [
                'name' => 'Scrum',
                'description' => 'Agile framework for managing complex projects',
                'skill_category' => 'Project Management'
            ],
            [
                'name' => 'Jira',
                'description' => 'Issue and project tracking tool',
                'skill_category' => 'Project Management'
            ],

            // Testing
            [
                'name' => 'Unit Testing',
                'description' => 'Testing individual components in isolation',
                'skill_category' => 'Testing'
            ],
            [
                'name' => 'Integration Testing',
                'description' => 'Testing interaction between integrated components',
                'skill_category' => 'Testing'
            ],
            [
                'name' => 'Selenium',
                'description' => 'Web application testing framework',
                'skill_category' => 'Testing'
            ],
        ];

        foreach ($skills as $skillData) {
            Skill::create($skillData);
        }

        User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        User::factory()->staff()->create([
            'username' => 'staff2x',
            'email' => 'staff2x@example.ac.uk',
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
