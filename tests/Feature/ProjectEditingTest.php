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
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::BUILD->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::TESTING->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::DEPLOYED->value);
        $project->advanceToNextStage();
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::COMPLETED->value);

    });

    it('shows all stage tabs to IT staff', function () {
        $itStaff = User::factory()->staff()->create();
        $this->actingAs($itStaff);

        $project = Project::factory()->create(['status' => ProjectStatus::IDEATION]);

        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Ideation')
            ->assertSee('Feasibility')
            ->assertSee('Scoping')
            ->assertSee('Scheduling')
            ->assertSee('Detailed Design')
            ->assertSee('Development')
            ->assertSee('Build')
            ->assertSee('Testing')
            ->assertSee('Deployed');
    });

    it('hides the other stage tabs from the requester who owns the project', function () {
        $requester = User::factory()->requester()->create();
        $this->actingAs($requester);

        $project = Project::factory()->create([
            'status' => ProjectStatus::IDEATION,
            'user_id' => $requester->id,
        ]);

        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Ideation')
            ->assertDontSee('Feasibility')
            ->assertDontSee('Scoping')
            ->assertDontSee('Scheduling')
            ->assertDontSee('Detailed Design')
            ->assertDontSee('Development')
            // no assertDontSee('Build'): the ideation form's strategic initiative
            // dropdown legitimately contains "Venture Builder"
            ->assertDontSee('Testing')
            ->assertDontSee('Deployed');
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
