<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ideation>
 */
class IdeationFactory extends Factory
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
            'objective' => fake()->sentence(),
            'business_case' => fake()->sentence(),
            'benefits' => fake()->paragraph(),
            'deadline' => fake()->dateTimeBetween('now', '+1 year'),
            'strategic_initiative' => fake()->sentence(),
        ];
    }
}
