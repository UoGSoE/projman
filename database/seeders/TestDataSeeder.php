<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Ideation;
use App\Models\Feasibility;
use App\Models\Role;
use App\Models\ProjectHistory;
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
