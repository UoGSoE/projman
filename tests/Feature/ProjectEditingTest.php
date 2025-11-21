<?php

use App\Enums\ProjectStatus;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Project Editing', function () {
    beforeEach(function () {
        // Fake notifications for this test suite (doesn't test notification behavior)
        $this->fakeNotifications();
    });

    it('can advance a project to the next stage', function () {
        // please check NotificationtTest.php for the correct emails being sent
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create(['status' => ProjectStatus::IDEATION]);

        $project->advanceToNextStage();
        expect($project->status->value)->toEqual(ProjectStatus::FEASIBILITY->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::SCOPING->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::SCHEDULING->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::DETAILED_DESIGN->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::DEVELOPMENT->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::TESTING->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::DEPLOYED->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::BUILD->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::COMPLETED->value);

    });

    it('livewire can advance the project to next stage', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create(['status' => ProjectStatus::IDEATION]);

        livewire(ProjectEditor::class, ['project' => $project])
            ->call('advanceToNextStage')
            ->assertHasNoErrors();

        expect($project->fresh()->status->value)->toEqual(ProjectStatus::FEASIBILITY->value);

    });
});
