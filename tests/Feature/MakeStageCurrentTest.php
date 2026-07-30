<?php

use App\Enums\ProjectStatus;
use App\Livewire\ProjectEditor;
use App\Mail\ProjectStageChangeMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->ensureProjectCreatedRoles();
});

function fillIdeationForm($component)
{
    return $component
        ->set('ideationForm.schoolGroup', 'Test School')
        ->set('ideationForm.objective', 'Updated Objective')
        ->set('ideationForm.businessCase', 'Test Business Case')
        ->set('ideationForm.benefits', 'Test Benefits')
        ->set('ideationForm.deadline', now()->addDay()->format('Y-m-d'))
        ->set('ideationForm.initiative', 'Inspire');
}

it('lets an admin save an earlier form and make that stage current', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::IDEATION);
    expect($project->fresh()->ideation->objective)->toBe('Updated Objective');
    expect($project->history()->pluck('description'))->toContain("Stage changed to ideation by {$admin->full_name}");
    expect($project->history()->pluck('description'))->not->toContain('Stage set to ideation');
    expect($project->history()->latest('id')->first()->user_id)->toBe($admin->id);
    Mail::assertQueued(ProjectStageChangeMail::class, 1);
});

it('forbids IT staff from calling $action', function (string $action) {
    $itStaff = User::factory()->staff()->create();
    $this->actingAs($itStaff);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->call($action, 'ideation')
        ->assertForbidden();

    expect($project->fresh()->status)->toBe(ProjectStatus::TESTING);
    expect($project->history()->count())->toBe(0);
    Mail::assertNotQueued(ProjectStageChangeMail::class);
})->with([
    'saveAndMakeStageCurrent',
    'saveAndMakeNextStageCurrent',
]);

it('just saves when the chosen stage is already current', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::IDEATION]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::IDEATION);
    expect($project->fresh()->ideation->objective)->toBe('Updated Objective');
    expect($project->history()->pluck('description'))->not->toContain("Stage changed to ideation by {$admin->full_name}");
    Mail::assertNotQueued(ProjectStageChangeMail::class);
});

it('does not change the stage when the form fails validation', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->set('ideationForm.objective', '')
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasErrors(['ideationForm.objective']);

    expect($project->fresh()->status)->toBe(ProjectStatus::TESTING);
    expect($project->history()->count())->toBe(0);
    Mail::assertNotQueued(ProjectStageChangeMail::class);
});

it('saves the form but refuses to change the stage of a $status project', function (ProjectStatus $status) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => $status]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe($status);
    expect($project->fresh()->ideation->objective)->toBe('Updated Objective');
    expect($project->history()->pluck('description'))->not->toContain("Stage changed to ideation by {$admin->full_name}");
    Mail::assertNotQueued(ProjectStageChangeMail::class);
})->with([
    'Cancelled' => [ProjectStatus::CANCELLED],
]);

it('lets an admin move a completed project back to an earlier stage', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::COMPLETED]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::IDEATION);
    expect($project->fresh()->ideation->objective)->toBe('Updated Objective');
    expect($project->history()->pluck('description'))->toContain("Stage changed to ideation by {$admin->full_name}");
    expect($project->history()->pluck('description'))->not->toContain('Stage set to ideation');
    Mail::assertQueued(ProjectStageChangeMail::class, 1);
});

it('lets an admin save an earlier form and make the stage after it current', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeNextStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::FEASIBILITY);
    expect($project->fresh()->ideation->objective)->toBe('Updated Objective');
    expect($project->history()->pluck('description'))->toContain("Stage changed to feasibility by {$admin->full_name}");
    Mail::assertQueued(ProjectStageChangeMail::class, 1);
});

it('skips Development when making the stage after Detailed Design current on a non-software project', function () {
    $admin = User::factory()->admin()->create();
    $designer = User::factory()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);
    $project->scoping->update(['requires_software_dev' => false]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->set('detailedDesignForm.designedBy', $designer->id)
        ->set('detailedDesignForm.serviceFunction', 'Test Service Function')
        ->set('detailedDesignForm.functionalRequirements', 'Test Functional Requirements')
        ->set('detailedDesignForm.nonFunctionalRequirements', 'Test Non-Functional Requirements')
        ->set('detailedDesignForm.hldDesignLink', 'https://example.com/design')
        ->call('saveAndMakeNextStageCurrent', 'detailed-design')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::BUILD);
    expect($project->fresh()->detailedDesign->service_function)->toBe('Test Service Function');
});

it('only changes the stage once when the action is double-clicked', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    fillIdeationForm(livewire(ProjectEditor::class, ['project' => $project]))
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->call('saveAndMakeStageCurrent', 'ideation')
        ->assertHasNoErrors();

    expect($project->fresh()->status)->toBe(ProjectStatus::IDEATION);
    expect($project->history()->pluck('description')->filter(fn ($entry) => $entry === "Stage changed to ideation by {$admin->full_name}"))->toHaveCount(1);
    Mail::assertQueued(ProjectStageChangeMail::class, 1);
});

it('shows the save dropdown options to an admin', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->assertSee('Save and make this stage current')
        ->assertSee('Save and make Feasibility current');
});

it('hides the save dropdown from IT staff who keep the advance button', function () {
    $itStaff = User::factory()->staff()->create();
    $this->actingAs($itStaff);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->assertDontSee('Save and make this stage current')
        ->assertDontSee('Save and make Feasibility current')
        ->assertDontSeeHtml('data-test="save-scoping-button"')
        ->assertSee('Advance to Next Stage');
});

it('offers the save dropdown on every stage form', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $project = Project::factory()->create(['status' => ProjectStatus::TESTING]);

    livewire(ProjectEditor::class, ['project' => $project])
        ->assertSee('Save and make Feasibility current')
        ->assertSee('Save and make Scoping current')
        ->assertSee('Save and make Scheduling current')
        ->assertSee('Save and make Detailed Design current')
        ->assertSee('Save and make Development current')
        ->assertSee('Save and make Build current')
        ->assertSee('Save and make Testing current')
        ->assertSee('Save and make Deployed current')
        ->assertSee('Save and make Completed current');
});
