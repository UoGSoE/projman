<?php

use App\Enums\SkillLevel;
use App\Models\Project;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminUser = User::factory()->admin()->create();
});

it('requires admin privileges to access the user overview', function () {
    $viewedUser = User::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('user.show', $viewedUser))->assertForbidden();
});

it('shows user details, roles, skills, requests, and IT assignments for admins', function () {
    $targetUser = User::factory()->create([
        'forenames' => 'Alice',
        'surname' => 'Example',
        'username' => 'alice.example',
        'email' => 'alice@example.test',
    ]);

    $role = Role::factory()->create(['name' => 'Project Coordinator']);
    $targetUser->roles()->attach($role);

    $skill = Skill::factory()->create(['name' => 'Networking']);
    $targetUser->skills()->attach($skill->id, ['skill_level' => SkillLevel::ADVANCED->value]);

    $requestedProject = Project::factory()->for($targetUser)->create([
        'title' => 'Campus WiFi Refresh',
    ]);

    $projectOwner = User::factory()->create();
    $itAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Helpdesk Rollout',
    ]);

    $itAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $this->actingAs($this->adminUser);

    $response = $this->get(route('user.show', $targetUser));

    $response->assertOk();
    $response->assertSeeText('Alice Example');
    $response->assertSeeText('Project Coordinator');
    $response->assertSeeText('Networking');
    $response->assertSeeText('Advanced');
    $response->assertSeeText($requestedProject->title);
    $response->assertSeeText($itAssignment->title);
    $response->assertSeeText('IT project assignments');
    $response->assertSeeText('1 active');
});

it('hides IT assignment information when the user has no skills', function () {
    $targetUser = User::factory()->create();

    $this->actingAs($this->adminUser);

    $response = $this->get(route('user.show', $targetUser));

    $response->assertOk();
    $response->assertDontSeeText('IT project assignments');
});
