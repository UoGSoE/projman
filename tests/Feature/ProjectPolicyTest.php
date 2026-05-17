<?php

use App\Enums\ProjectStatus;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

describe('ProjectPolicy view ability', function () {
    it('forbids a requester from viewing another users project', function () {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('project.show', $project))
            ->assertForbidden();
    });

    it('allows the owner to view their own project', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful();
    });

    it('allows an admin to view any project', function () {
        $owner = User::factory()->requester()->create();
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->get(route('project.show', $project))
            ->assertSuccessful();
    });

    it('allows IT staff to view any project', function () {
        $owner = User::factory()->requester()->create();
        $itStaff = User::factory()->staff()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($itStaff)
            ->get(route('project.show', $project))
            ->assertSuccessful();
    });
});

describe('Project show page edit button', function () {
    it('hides the edit button from the owner once the project has left ideation', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::FEASIBILITY,
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertDontSee(route('project.edit', $project));
    });

    it('still shows the edit button to the owner while the project is in ideation', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::IDEATION,
        ]);

        $this->actingAs($owner)
            ->get(route('project.show', $project))
            ->assertSuccessful()
            ->assertSee(route('project.edit', $project));
    });
});

describe('ProjectPolicy create ability', function () {
    it('allows any authenticated user to reach the create page', function () {
        $user = User::factory()->requester()->create();

        $this->actingAs($user)
            ->get(route('project.create'))
            ->assertSuccessful();
    });
});

describe('ProjectPolicy update ability', function () {
    it('forbids a requester from reaching the edit page of another users project', function () {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('project.edit', $project))
            ->assertForbidden();
    });

    it('allows the owner to edit their project while it is in ideation', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::IDEATION,
        ]);

        $this->actingAs($owner)
            ->get(route('project.edit', $project))
            ->assertSuccessful();
    });

    it('forbids the owner from reaching the edit page once the project has left ideation', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::FEASIBILITY,
        ]);

        $this->actingAs($owner)
            ->get(route('project.edit', $project))
            ->assertForbidden();
    });
});

describe('ProjectPolicy cancel ability', function () {
    it('allows the owner to cancel their own project', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        expect($owner->can('cancel', $project))->toBeTrue();
    });

    it('allows an admin to cancel any project', function () {
        $owner = User::factory()->requester()->create();
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        expect($admin->can('cancel', $project))->toBeTrue();
    });

    it('forbids a non-owner non-admin from cancelling a project', function () {
        $owner = User::factory()->requester()->create();
        $itStaff = User::factory()->staff()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        expect($itStaff->can('cancel', $project))->toBeFalse();
    });
});

describe('ProjectEditor in-method authorisation', function () {
    it('forbids a requester from calling advanceToNextStage via Livewire', function () {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::IDEATION,
        ]);

        $this->actingAs($intruder);

        livewire(ProjectEditor::class, ['project' => $project])
            ->call('advanceToNextStage')
            ->assertForbidden();

        expect($project->fresh()->status)->toBe(ProjectStatus::IDEATION);
    });

    it('forbids the owner from saving a non-ideation form even while in ideation', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::IDEATION,
        ]);

        $this->actingAs($owner);

        livewire(ProjectEditor::class, ['project' => $project])
            ->call('save', 'feasibility')
            ->assertForbidden();
    });

    it('forbids a requester from calling governance actions', function (string $method) {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->requester()->create();
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => ProjectStatus::IDEATION,
        ]);

        $this->actingAs($intruder);

        livewire(ProjectEditor::class, ['project' => $project])
            ->call($method)
            ->assertForbidden();
    })->with([
        'approveFeasibility',
        'rejectFeasibility',
        'submitScoping',
        'submitSchedulingToDCGG',
        'scheduleScheduling',
        'requestUAT',
        'requestServiceAcceptance',
        'submitTesting',
        'acceptDeploymentService',
        'approveDeployment',
        'addDevelopmentNote',
        'addBuildNote',
    ]);
});
