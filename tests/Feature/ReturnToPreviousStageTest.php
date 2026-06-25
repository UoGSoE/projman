<?php

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

it('returns from $from back to $to', function (ProjectStatus $from, ProjectStatus $to) {
    $project = Project::factory()->create(['status' => $from]);

    $project->returnToPreviousStage();

    expect($project->fresh()->status)->toBe($to);
})->with([
    'Scoping -> Feasibility' => [ProjectStatus::SCOPING, ProjectStatus::FEASIBILITY],
    'Scheduling -> Scoping' => [ProjectStatus::SCHEDULING, ProjectStatus::SCOPING],
    'Detailed Design -> Scheduling' => [ProjectStatus::DETAILED_DESIGN, ProjectStatus::SCHEDULING],
]);

it('writes a ProjectHistory entry when the editor returns to the previous stage', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->call('returnToPreviousStage')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::SCOPING);
    expect($project->history()->first()->description)->toBe('Returned to scoping');
    expect($project->history()->first()->user_id)->toBe($admin->id);
});

it('forbids a non-admin owner from rolling back a project past Ideation', function () {
    $owner = User::factory()->requester()->create();
    $this->actingAs($owner);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'status' => ProjectStatus::SCOPING,
    ]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->call('returnToPreviousStage')
        ->assertForbidden();

    expect($project->fresh()->status)->toBe(ProjectStatus::SCOPING);
    expect($project->history()->count())->toBe(0);
});

it('renders the return-to-previous-stage button on eligible stages only', function (ProjectStatus $status, bool $shouldSee) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => $status]);

    $test = livewire(ProjectEditor::class, ['project' => $project]);

    if ($shouldSee) {
        $test->assertSeeHtml('data-test="return-to-previous-stage-button"');
    } else {
        $test->assertDontSeeHtml('data-test="return-to-previous-stage-button"');
    }
})->with([
    'Ideation hides' => [ProjectStatus::IDEATION, false],
    'Feasibility hides' => [ProjectStatus::FEASIBILITY, false],
    'Scoping shows' => [ProjectStatus::SCOPING, true],
    'Scheduling shows' => [ProjectStatus::SCHEDULING, true],
    'Detailed Design shows' => [ProjectStatus::DETAILED_DESIGN, true],
    'Development hides' => [ProjectStatus::DEVELOPMENT, false],
    'Completed hides' => [ProjectStatus::COMPLETED, false],
    'Cancelled hides' => [ProjectStatus::CANCELLED, false],
]);

it('does nothing when the editor action is called from an ineligible stage', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::DEVELOPMENT]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->call('returnToPreviousStage')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::DEVELOPMENT);
    expect($project->history()->count())->toBe(0);
});

it('lets an admin edit and save the scheduling form after rolling back from detailed design', function () {
    $admin = User::factory()->admin()->create();
    $assignedUser = User::factory()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::DETAILED_DESIGN]);
    $project->scheduling->update([
        'key_skills' => 'PHP, Laravel',
        'priority' => Priority::PRIORITY_2->value,
        'assigned_to' => $assignedUser->id,
        'estimated_start_date' => now()->addDays(7),
        'estimated_completion_date' => now()->addDays(30),
        'change_board_date' => now()->addDays(5),
    ]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->call('returnToPreviousStage')
        ->set('schedulingForm.keySkills', 'Updated skills after rollback')
        ->call('save', 'scheduling')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::SCHEDULING);
    expect($project->fresh()->scheduling->key_skills)->toBe('Updated skills after rollback');
});

it('refuses to roll back from ineligible stages', function (ProjectStatus $from) {
    $project = Project::factory()->create(['status' => $from]);

    expect(fn () => $project->returnToPreviousStage())
        ->toThrow(HttpException::class);

    expect($project->fresh()->status)->toBe($from);
})->with([
    'Ideation' => [ProjectStatus::IDEATION],
    'Feasibility' => [ProjectStatus::FEASIBILITY],
    'Development' => [ProjectStatus::DEVELOPMENT],
    'Completed' => [ProjectStatus::COMPLETED],
    'Cancelled' => [ProjectStatus::CANCELLED],
]);
