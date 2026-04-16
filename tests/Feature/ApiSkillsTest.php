<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authHeader(User $user): array
{
    $token = $user->createToken('Test')->plainTextToken;

    return ['Authorization' => "Bearer {$token}"];
}

it('lists skills in a paginated response', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    Skill::factory()->create(['name' => 'Python', 'skill_category' => 'Languages']);
    Skill::factory()->create(['name' => 'Kubernetes', 'skill_category' => 'Ops']);

    $response = $this->withHeaders(authHeader($admin))
        ->getJson('/api/skills')
        ->assertOk();

    $response->assertJsonStructure([
        'data' => [['id', 'name', 'description', 'skill_category']],
        'links',
        'meta',
    ]);
    expect($response->json('data'))->toHaveCount(2);
});

it('lists users who hold a given skill with their levels', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $skill = Skill::factory()->create(['name' => 'Python']);

    $alice = User::factory()->create(['is_staff' => true, 'surname' => 'Alice']);
    $bob = User::factory()->create(['is_staff' => true, 'surname' => 'Bob']);
    $alice->updateSkill($skill->id, SkillLevel::WORKING->value);
    $bob->updateSkill($skill->id, SkillLevel::EXPERT->value);

    $response = $this->withHeaders(authHeader($admin))
        ->getJson("/api/skills/{$skill->id}/users")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);

    $aliceRow = collect($response->json('data'))->firstWhere('id', $alice->id);
    expect($aliceRow['skills'][0]['level'])->toBe('working');
    expect($aliceRow['skills'][0]['level_value'])->toBe(2);

    $bobRow = collect($response->json('data'))->firstWhere('id', $bob->id);
    expect($bobRow['skills'][0]['level'])->toBe('expert');
    expect($bobRow['skills'][0]['level_value'])->toBe(4);
});

it('returns 404 for a non-existent skill id on the users endpoint', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);

    $this->withHeaders(authHeader($admin))
        ->getJson('/api/skills/99999/users')
        ->assertNotFound();
});

it('rejects unauthenticated requests to the skills endpoints', function () {
    $skill = Skill::factory()->create();

    $this->getJson('/api/skills')->assertUnauthorized();
    $this->getJson("/api/skills/{$skill->id}/users")->assertUnauthorized();
});
