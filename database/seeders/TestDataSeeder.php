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
    // Stop the model events from firing to prevent side effects so we can seed exactly what we want
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.ac.uk',
            'password' => bcrypt('secret'),
        ]);

        $users = User::factory()->count(10)->create();

        foreach (User::all() as $user) {
            foreach (range(1, 10) as $i) {
                $project = Project::factory()->create([
                    'user_id' => $user->id,
                    'updated_at' => now()->subDays(rand(1, 100)),
                ]);
                $stage = rand(0, count(config('projman.subforms')) - 1);
                foreach (config('projman.subforms') as $index => $formName) {
                    $formName::factory()->create([
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
