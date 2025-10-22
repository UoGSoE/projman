<?php

namespace Database\Factories;

use App\Events\ProjectStageChange;
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
        $recipientType = fake()->randomElement(['roles', 'users']);
        $recipients = $recipientType === 'roles' ? ['roles' => Role::pluck('id')->random(5)->toArray()] : ['users' => User::pluck('id')->random(5)->toArray()];
        $eventClass = fake()->randomElement(array_column(config('notifiable_events'), 'class'));

        $event = [
            'class' => $eventClass,
        ];

        if ($eventClass === ProjectStageChange::class) {
            $event['project_stage'] = fake()->randomElement([
                'ideation', 'feasibility', 'scoping', 'scheduling',
                'detailed-design', 'development', 'testing', 'deployed',
            ]);
        }

        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'event' => $event,
            'recipients' => $recipients,
            'active' => fake()->boolean(90),
        ];
    }
}
