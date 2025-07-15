<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Development>
 */
class DevelopmentFactory extends Factory
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
            'lead_developer' => User::factory(),
            'development_team' => [],
            'technical_approach' => fake()->paragraph(),
            'development_notes' => fake()->paragraph(),
            'repository_link' => fake()->url(),
            'status' => fake()->randomElement(['planning', 'in_progress', 'review', 'completed']),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'completion_date' => fake()->dateTimeBetween('now', '+6 months'),
            'code_review_notes' => fake()->paragraph(),
        ];
    }
}
