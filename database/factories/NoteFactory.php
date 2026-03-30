<?php

namespace Database\Factories;

use App\Models\Build;
use App\Models\Development;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'noteable_type' => Development::class,
            'noteable_id' => Development::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->paragraph,
        ];
    }

    public function forDevelopment(Development $development): static
    {
        return $this->state(fn (array $attributes) => [
            'noteable_type' => Development::class,
            'noteable_id' => $development->id,
        ]);
    }

    public function forBuild(Build $build): static
    {
        return $this->state(fn (array $attributes) => [
            'noteable_type' => Build::class,
            'noteable_id' => $build->id,
        ]);
    }
}
