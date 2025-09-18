<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scoping>
 */
class ScopingFactory extends Factory
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
            'estimated_effort' => fake()->paragraph(),
            'in_scope' => fake()->paragraph(),
            'out_of_scope' => fake()->paragraph(),
            'assumptions' => fake()->paragraph(),
            'skills_required' => null,
        ];
    }
}
