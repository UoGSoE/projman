<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
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
            'deployment_lead_id' => User::factory(),
            'service_function' => fake()->randomElement([
                'Applications & Data',
                'College Infrastructure',
                'Research Computing',
                'Service Resilience',
                'Service Delivery',
            ]),
            'functional_tests' => fake()->paragraph(),
            'non_functional_tests' => fake()->paragraph(),
            'bau_operational_wiki' => fake()->url(),
            'service_resilience_approval' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_resilience_notes' => fake()->paragraph(),
            'service_operations_approval' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_operations_notes' => fake()->paragraph(),
            'service_delivery_approval' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_delivery_notes' => fake()->paragraph(),
            'service_accepted_at' => null,
            'deployment_approved_at' => null,
        ];
    }

    /**
     * State for a deployed record ready for service acceptance.
     * All required fields are filled with valid data.
     */
    public function readyForServiceAcceptance(): static
    {
        return $this->state(fn (array $attributes) => [
            'deployment_lead_id' => User::factory(),
            'service_function' => 'Applications & Data',
            'functional_tests' => 'FR1: Test functional requirement one',
            'non_functional_tests' => 'NFR1: Test non-functional requirement one',
            'bau_operational_wiki' => 'https://wiki.example.com',
            'service_accepted_at' => null,
            'deployment_approved_at' => null,
        ]);
    }

    /**
     * State for a deployed record that has been service accepted.
     */
    public function serviceAccepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_accepted_at' => now(),
        ]);
    }

    /**
     * State for a deployed record ready for final approval.
     * Service has been accepted and all 3 service handover approvals are received.
     */
    public function readyForApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_accepted_at' => now(),
            'service_resilience_approval' => 'approved',
            'service_operations_approval' => 'approved',
            'service_delivery_approval' => 'approved',
        ]);
    }

    /**
     * State for a deployed record with incomplete required fields.
     * Used for testing validation failures.
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'deployment_lead_id' => null,
        ]);
    }
}
