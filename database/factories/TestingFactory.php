<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Testing>
 */
class TestingFactory extends Factory
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
            'test_lead' => User::factory(),
            'service_function' => fake()->sentence(),
            'functional_testing_title' => fake()->sentence(),
            'functional_tests' => fake()->paragraph(),
            'non_functional_testing_title' => fake()->sentence(),
            'non_functional_tests' => fake()->paragraph(),
            'test_repository' => fake()->url(),
            'testing_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'user_acceptance' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'testing_lead_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_delivery_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'service_resilience_sign_off' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
