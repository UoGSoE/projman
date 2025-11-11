<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DetailedDesign>
 */
class DetailedDesignFactory extends Factory
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
            'designed_by' => User::factory(),
            'service_function' => fake()->sentence(),
            'functional_requirements' => fake()->paragraph(),
            'non_functional_requirements' => fake()->paragraph(),
            'hld_design_link' => fake()->url(),
            'approval_delivery' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'approval_operations' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'approval_resilience' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'approval_change_board' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
