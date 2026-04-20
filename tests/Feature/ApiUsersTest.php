<?php

use App\Enums\Busyness;
use App\Enums\ServiceFunction;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bearerFor(User $user): array
{
    $token = $user->createToken('Test')->plainTextToken;

    return ['Authorization' => "Bearer {$token}"];
}

it('lists staff users in a paginated response and excludes non-staff', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    User::factory()->create(['is_staff' => true, 'surname' => 'Staffer']);
    User::factory()->requester()->create(['surname' => 'NotStaff']);

    $response = $this->withHeaders(bearerFor($admin))
        ->getJson('/api/users')
        ->assertOk();

    $surnames = collect($response->json('data'))->pluck('surname');
    expect($surnames)->toContain('Staffer');
    expect($surnames)->not->toContain('NotStaff');

    $response->assertJsonStructure([
        'data' => [['id', 'username', 'forenames', 'surname', 'email', 'is_staff']],
        'links',
        'meta',
    ]);
});

it('includes the users skills with their levels when present', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $staffer = User::factory()->create(['is_staff' => true, 'surname' => 'Smith']);
    $python = Skill::factory()->create(['name' => 'Python']);
    $staffer->updateSkill($python->id, SkillLevel::PRACTITIONER->value);

    $response = $this->withHeaders(bearerFor($admin))
        ->getJson('/api/users')
        ->assertOk();

    $stafferRow = collect($response->json('data'))->firstWhere('id', $staffer->id);

    expect($stafferRow['skills'])->toHaveCount(1);
    expect($stafferRow['skills'][0]['name'])->toBe('Python');
    expect($stafferRow['skills'][0]['level'])->toBe('practitioner');
    expect($stafferRow['skills'][0]['level_value'])->toBe(3);
});

it('returns a single user\'s skills with levels', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $staffer = User::factory()->create(['is_staff' => true]);
    $python = Skill::factory()->create(['name' => 'Python']);
    $k8s = Skill::factory()->create(['name' => 'Kubernetes']);
    $staffer->updateSkill($python->id, SkillLevel::WORKING->value);
    $staffer->updateSkill($k8s->id, SkillLevel::AWARENESS->value);

    $response = $this->withHeaders(bearerFor($admin))
        ->getJson("/api/users/{$staffer->id}/skills")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);

    $pythonRow = collect($response->json('data'))->firstWhere('name', 'Python');
    expect($pythonRow['level'])->toBe('working');
    expect($pythonRow['level_value'])->toBe(2);
});

it('returns 404 for a non-existent user id on the skills endpoint', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);

    $this->withHeaders(bearerFor($admin))
        ->getJson('/api/users/99999/skills')
        ->assertNotFound();
});

it('rejects unauthenticated requests to the users endpoints', function () {
    $staffer = User::factory()->create(['is_staff' => true]);

    $this->getJson('/api/users')->assertUnauthorized();
    $this->getJson("/api/users/{$staffer->id}/skills")->assertUnauthorized();
});

it('surfaces busyness and service_function on the /api/users payload', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $staffer = User::factory()->create([
        'is_staff' => true,
        'surname' => 'Workhorse',
        'service_function' => ServiceFunction::RESEARCH_COMPUTING,
        'busyness_week_1' => Busyness::LOW,
        'busyness_week_2' => Busyness::HIGH,
    ]);

    $response = $this->withHeaders(bearerFor($admin))
        ->getJson('/api/users')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $staffer->id);

    expect($row['service_function'])->toBe('research_computing');
    expect($row['busyness_week_1'])->toBe('low');
    expect($row['busyness_week_1_value'])->toBe(30);
    expect($row['busyness_week_2'])->toBe('high');
    expect($row['busyness_week_2_value'])->toBe(90);
});
