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

        foreach (User::all() as $user) {
            foreach (range(1, 10) as $i) {
                $project = Project::factory()->create([
                    'user_id' => $user->id,
                    'updated_at' => now()->subDays(rand(1, 100)),
                ]);
                Ideation::factory()->create([
                    'project_id' => $project->id,
                ]);

                if (rand(0, 1)) {
                    Feasibility::factory()->create([
                        'project_id' => $project->id,
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
