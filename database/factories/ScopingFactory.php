<?php

namespace Database\Factories;

use App\Enums\EffortScale;
use App\Models\Project;
use App\Models\Skill;
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
            'estimated_effort' => EffortScale::MEDIUM,
            'in_scope' => substr(fake()->paragraph(), 0, 250),
            'out_of_scope' => substr(fake()->paragraph(), 0, 250),
            'assumptions' => substr(fake()->paragraph(), 0, 250),
            'skills_required' => Skill::count() > 0
                ? Skill::inRandomOrder()->take(3)->pluck('id')->toArray()
                : Skill::factory()->count(3)->create()->pluck('id')->toArray(),
        ];
    }
}
