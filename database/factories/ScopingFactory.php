<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
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
            'estimated_effort' => substr(fake()->paragraph(), 0, 250),
            'in_scope' => substr(fake()->paragraph(), 0, 250),
            'out_of_scope' => substr(fake()->paragraph(), 0, 250),
            'assumptions' => substr(fake()->paragraph(), 0, 250),
            'skills_required' => null,
        ];
    }
}
