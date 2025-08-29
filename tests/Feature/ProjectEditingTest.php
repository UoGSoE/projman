<?php
use App\Enums\ProjectStatus;
use App\Livewire\ProjectCreator;
use App\Livewire\ProjectEditor;
use App\Mail\ProjectCreatedMail;
use App\Models\Project;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;


uses(RefreshDatabase::class);

describe('Project Editing', function () {
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
        expect($project->fresh()->status->value)->toEqual(ProjectStatus::COMPLETED->value);

    });

    it("livewire can advance the project to next stage", function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create(['status' => ProjectStatus::IDEATION]);

        livewire(ProjectEditor::class, ['project' => $project])
            ->call('advanceToNextStage')
            ->assertHasNoErrors();

        expect($project->fresh()->status->value)->toEqual(ProjectStatus::FEASIBILITY->value);

    });
});
