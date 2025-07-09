<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Ideation;
use App\Models\Feasibility;
use App\Models\ProjectHistory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        $users = User::factory()->count(10)->create();

        $formNames = [
            \App\Models\Ideation::class,
            \App\Models\Feasibility::class,
            \App\Models\Development::class,
            \App\Models\Testing::class,
            \App\Models\Deployed::class,
            \App\Models\Scoping::class,
            \App\Models\Scheduling::class,
            \App\Models\DetailedDesign::class,
        ];

        foreach (User::all() as $user) {
            foreach (range(1, 10) as $i) {
                $project = Project::factory()->create([
                    'user_id' => $user->id,
                    'updated_at' => now()->subDays(rand(1, 100)),
                ]);
                $stage = rand(0, count($formNames) - 1);
                foreach ($formNames as $index => $formName) {
                    $form = $formName::factory()->create([
                        'project_id' => $project->id,
                        'created_at' => $index <= $stage ? now()->subDays(rand(1, 100)) : now(),
                    ]);
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
