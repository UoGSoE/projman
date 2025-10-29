<?php

use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
use App\Livewire\UserViewer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

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

    $completedAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Legacy CRM Migration',
        'status' => ProjectStatus::COMPLETED,
    ]);

    $completedAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $cancelledAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Warehouse Rewrite',
        'status' => ProjectStatus::CANCELLED,
    ]);

    $cancelledAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $this->actingAs($this->adminUser);

    $response = $this->get(route('user.show', $targetUser));

    $response->assertOk();
    $response->assertSeeText('Alice Example');
    $response->assertSeeText('Project Coordinator');
    $response->assertSeeText('Networking');
    $response->assertSeeText($requestedProject->title);
    $response->assertSeeText($itAssignment->title);
    $response->assertSeeText('IT project assignments');
    $response->assertSeeText('1 assignments');
    $response->assertDontSeeText($completedAssignment->title);
    $response->assertDontSeeText($cancelledAssignment->title);
});

it('hides IT assignment information when the user has no skills', function () {
    $targetUser = User::factory()->create();

    $this->actingAs($this->adminUser);

    $response = $this->get(route('user.show', $targetUser));

    $response->assertOk();
    $response->assertDontSeeText('IT project assignments');
});

it('can toggle to include completed and cancelled assignments', function () {
    $targetUser = User::factory()->create([
        'forenames' => 'Alex',
        'surname' => 'Toggle',
    ]);

    $skill = Skill::factory()->create(['name' => 'Systems']);
    $targetUser->skills()->attach($skill->id, ['skill_level' => SkillLevel::BEGINNER->value]);

    $projectOwner = User::factory()->create();

    $activeAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Active Assignment',
        'status' => ProjectStatus::IDEATION,
    ]);
    $activeAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $completedAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Completed Assignment',
        'status' => ProjectStatus::COMPLETED,
    ]);
    $completedAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $cancelledAssignment = Project::factory()->for($projectOwner)->create([
        'title' => 'Cancelled Assignment',
        'status' => ProjectStatus::CANCELLED,
    ]);
    $cancelledAssignment->scheduling()->create([
        'cose_it_staff' => [$targetUser->id],
    ]);

    $this->actingAs($this->adminUser);

    livewire(UserViewer::class, ['user' => $targetUser])
        ->assertSet('showAllAssignments', false)
        ->assertSeeText($activeAssignment->title)
        ->assertDontSeeText($completedAssignment->title)
        ->set('showAllAssignments', true)
        ->assertSeeText($completedAssignment->title)
        ->assertSeeText($cancelledAssignment->title)
        ->assertSeeText('3 assignments');
});
