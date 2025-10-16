<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationRule>
 */
class NotificationRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $applyToAll = fake()->boolean(60);
        $appliesTo = $applyToAll ? ['all'] : Project::pluck('id')->random(5)->toArray();
        $recipientType = fake()->randomElement(['roles', 'users']);
        $recipients = $recipientType === 'roles' ? ['roles' => Role::pluck('id')->random(5)->toArray()] : ['users' => User::pluck('id')->random(5)->toArray()];
        $event = fake()->randomElement(array_column(config('notifiable_events'), 'class'));

        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'event' => $event,
            'applies_to' => $applyToAll ? ['all'] : Project::pluck('id')->random(5)->toArray(),
            'recipients' => $recipients,
            'active' => fake()->boolean(90),
        ];
    }
}
