<?php

namespace Database\Factories;

use App\Models\DetailedDesign;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetailedDesign>
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
        $changeBoardApproval = fake()->randomElement(['pending', 'approved', 'rejected']);

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
            'approval_change_board' => $changeBoardApproval,
            'approval_agb' => $changeBoardApproval,
        ];
    }

    /**
     * Pins every approval to a deterministic 'pending'; the valid form data
     * itself comes from definition().
     */
    public function complete(): static
    {
        return $this->state(fn () => [
            'approval_delivery' => 'pending',
            'approval_operations' => 'pending',
            'approval_resilience' => 'pending',
            'approval_change_board' => 'pending',
            'approval_agb' => 'pending',
        ]);
    }
}
