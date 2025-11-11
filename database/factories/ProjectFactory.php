<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school_group' => fake()->randomElement(['School of Computing', 'School of Engineering', 'School of Business', 'School of Law', 'School of Humanities', 'School of Social Sciences']),
            'title' => fake()->sentence(),
            'deadline' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => ProjectStatus::IDEATION,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::COMPLETED,
        ]);
    }
}
