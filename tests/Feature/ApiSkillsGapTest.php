<?php

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function gapBearerFor(User $user): array
{
    $token = $user->createToken('Test')->plainTextToken;

    return ['Authorization' => "Bearer {$token}"];
}

it('returns per-skill counts by level with totals', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $python = Skill::factory()->create(['name' => 'Python', 'skill_category' => 'Languages']);

    $working1 = User::factory()->create(['is_staff' => true]);
    $working2 = User::factory()->create(['is_staff' => true]);
    $working3 = User::factory()->create(['is_staff' => true]);
    $expert = User::factory()->create(['is_staff' => true]);

    $working1->updateSkill($python->id, SkillLevel::WORKING->value);
    $working2->updateSkill($python->id, SkillLevel::WORKING->value);
    $working3->updateSkill($python->id, SkillLevel::WORKING->value);
    $expert->updateSkill($python->id, SkillLevel::EXPERT->value);

    $response = $this->withHeaders(gapBearerFor($admin))
        ->getJson('/api/stats/skills-gap')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('skill_id', $python->id);

    expect($row)->toBe([
        'skill_id' => $python->id,
        'skill_name' => 'Python',
        'skill_category' => 'Languages',
        'counts' => [
            'awareness' => 0,
            'working' => 3,
            'practitioner' => 0,
            'expert' => 1,
        ],
        'total' => 4,
    ]);
});

it('includes skills that have zero users with all counts at zero', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $orphan = Skill::factory()->create(['name' => 'COBOL', 'skill_category' => 'Legacy']);

    $response = $this->withHeaders(gapBearerFor($admin))
        ->getJson('/api/stats/skills-gap')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('skill_id', $orphan->id);

    expect($row['counts'])->toBe(['awareness' => 0, 'working' => 0, 'practitioner' => 0, 'expert' => 0]);
    expect($row['total'])->toBe(0);
});

it('excludes non-staff users from the counts', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $skill = Skill::factory()->create(['name' => 'Python']);
    $staffer = User::factory()->create(['is_staff' => true]);
    $nonStaff = User::factory()->create(['is_staff' => false]);

    $staffer->updateSkill($skill->id, SkillLevel::WORKING->value);
    $nonStaff->updateSkill($skill->id, SkillLevel::EXPERT->value);

    $response = $this->withHeaders(gapBearerFor($admin))
        ->getJson('/api/stats/skills-gap')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('skill_id', $skill->id);

    expect($row['counts']['working'])->toBe(1);
    expect($row['counts']['expert'])->toBe(0);
    expect($row['total'])->toBe(1);
});

it('rejects unauthenticated requests to the skills-gap endpoint', function () {
    $this->getJson('/api/stats/skills-gap')->assertUnauthorized();
});
