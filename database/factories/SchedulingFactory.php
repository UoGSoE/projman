<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scheduling>
 */
class SchedulingFactory extends Factory
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
            'key_skills' => fake()->paragraph(),
            'cose_it_staff' => [],
            'estimated_start_date' => fake()->dateTimeBetween('now', '+3 months'),
            'estimated_completion_date' => fake()->dateTimeBetween('+3 months', '+1 year'),
            'change_board_date' => fake()->dateTimeBetween('now', '+1 month'),
            'assigned_to' => User::factory(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'team_assignment' => fake()->words(3, true),
        ];
    }
}
