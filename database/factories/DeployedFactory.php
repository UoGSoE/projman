<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deployed>
 */
class DeployedFactory extends Factory
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
            'deployed_by' => User::factory(),
            'environment' => fake()->randomElement(['development', 'staging', 'production']),
            'status' => fake()->randomElement(['pending', 'deployed', 'failed', 'rolled_back']),
            'deployment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'version' => fake()->semver(),
            'production_url' => fake()->url(),
            'deployment_notes' => fake()->paragraph(),
            'rollback_plan' => fake()->paragraph(),
            'monitoring_notes' => fake()->paragraph(),
            'deployment_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'operations_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'user_acceptance' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_delivery_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'change_advisory_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
