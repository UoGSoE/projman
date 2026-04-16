<?php

use App\Enums\Busyness;
use App\Enums\ServiceFunction;
use App\Enums\SkillLevel;
use App\Http\Resources\SkillResource;
use App\Http\Resources\UserResource;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shapes a SkillResource with id, name, description and category', function () {
    $skill = Skill::factory()->create([
        'name' => 'Python',
        'description' => 'Programming language',
        'skill_category' => 'Languages',
    ]);

    $resource = (new SkillResource($skill))->resolve();

    expect($resource)->toBe([
        'id' => $skill->id,
        'name' => 'Python',
        'description' => 'Programming language',
        'skill_category' => 'Languages',
    ]);
});

it('shapes a UserResource without skills when they are not loaded', function () {
    $user = User::factory()->create([
        'username' => 'jsmith',
        'forenames' => 'Jane',
        'surname' => 'Smith',
        'email' => 'jane@example.ac.uk',
        'is_staff' => true,
    ]);

    $resource = (new UserResource($user))->resolve();

    expect($resource)->toMatchArray([
        'id' => $user->id,
        'username' => 'jsmith',
        'forenames' => 'Jane',
        'surname' => 'Smith',
        'email' => 'jane@example.ac.uk',
        'is_staff' => true,
    ]);
    expect($resource)->not->toHaveKey('skills');
});

it('includes skills with level and numeric level_value when skills are loaded', function () {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create([
        'name' => 'Python',
        'description' => 'Programming language',
        'skill_category' => 'Languages',
    ]);
    $user->updateSkill($skill->id, SkillLevel::WORKING->value);
    $user->load('skills');

    $resource = (new UserResource($user))->toResponse(request())->getData(true);

    expect($resource['data']['skills'])->toBe([
        [
            'id' => $skill->id,
            'name' => 'Python',
            'description' => 'Programming language',
            'skill_category' => 'Languages',
            'level' => 'working',
            'level_value' => 2,
        ],
    ]);
});

it('exposes busyness and service_function on UserResource', function () {
    $user = User::factory()->create([
        'is_staff' => true,
        'service_function' => ServiceFunction::APPLICATIONS_DATA,
        'busyness_week_1' => Busyness::MEDIUM,
        'busyness_week_2' => Busyness::HIGH,
    ]);

    $resource = (new UserResource($user))->resolve();

    expect($resource['service_function'])->toBe('applications_data');
    expect($resource['busyness_week_1'])->toBe('medium');
    expect($resource['busyness_week_1_value'])->toBe(60);
    expect($resource['busyness_week_2'])->toBe('high');
    expect($resource['busyness_week_2_value'])->toBe(90);
});
