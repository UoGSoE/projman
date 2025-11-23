<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feasibility>
 */
class FeasibilityFactory extends Factory
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
            'assessed_by' => User::factory(),
            'date_assessed' => fake()->dateTimeBetween('-1 year', 'now'),
            'technical_credence' => fake()->sentence(),
            'cost_benefit_case' => fake()->paragraph(),
            'dependencies_prerequisites' => fake()->paragraph(),
            'deadlines_achievable' => fake()->boolean(),
            'alternative_proposal' => fake()->paragraph(),
            'existing_solution_status' => null,
            'existing_solution_notes' => null,
            'off_the_shelf_solution_status' => null,
            'off_the_shelf_solution_notes' => null,
        ];
    }

    public function withExistingSolution(): static
    {
        return $this->state(fn (array $attributes) => [
            'existing_solution_status' => 'yes',
            'existing_solution_notes' => fake()->paragraph(),
        ]);
    }

    public function withOffTheShelfSolution(): static
    {
        return $this->state(fn (array $attributes) => [
            'off_the_shelf_solution_status' => 'yes',
            'off_the_shelf_solution_notes' => fake()->paragraph(),
        ]);
    }

    public function withNoExistingSolution(): static
    {
        return $this->state(fn (array $attributes) => [
            'existing_solution_status' => 'no',
            'off_the_shelf_solution_status' => 'no',
        ]);
    }
}
