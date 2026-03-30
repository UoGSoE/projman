<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectHistory>
 */
class ProjectHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'description' => $this->faker->sentence,
        ];
    }
}
