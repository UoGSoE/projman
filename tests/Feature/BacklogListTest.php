<?php

use App\Enums\ProjectStatus;
use App\Livewire\BacklogList;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setupBaseNotificationRoles();
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->user);
});

test('backlog list page renders successfully', function () {
    $response = $this->get(route('portfolio.backlog'));

    $response->assertOk();
    $response->assertSeeLivewire(BacklogList::class);
});

test('displays active projects in backlog', function () {
    $activeProject = Project::factory()->create([
        'title' => 'Active Project One',
        'status' => ProjectStatus::SCOPING,
    ]);

    $completedProject = Project::factory()->create([
        'title' => 'Completed Project',
        'status' => ProjectStatus::COMPLETED,
    ]);

    $cancelledProject = Project::factory()->create([
        'title' => 'Cancelled Project',
        'status' => ProjectStatus::CANCELLED,
    ]);

    livewire(BacklogList::class)
        ->assertSee('Active Project One')
        ->assertDontSee('Completed Project')
        ->assertDontSee('Cancelled Project');
});

test('search filter works correctly', function () {
    Project::factory()->create(['title' => 'Data Migration Project']);
    Project::factory()->create(['title' => 'Website Redesign Project']);

    livewire(BacklogList::class)
        ->set('search', 'Migration')
        ->assertSee('Data Migration Project')
        ->assertDontSee('Website Redesign Project');
});

test('status filter works correctly', function () {
    $scopingProject = Project::factory()->create([
        'title' => 'Scoping Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    $schedulingProject = Project::factory()->create([
        'title' => 'Scheduling Project',
        'status' => ProjectStatus::SCHEDULING,
    ]);

    livewire(BacklogList::class)
        ->set('statusFilter', ProjectStatus::SCOPING->value)
        ->assertSee('Scoping Project')
        ->assertDontSee('Scheduling Project');
});

test('shows all statuses when filter is set to all', function () {
    $scopingProject = Project::factory()->create([
        'title' => 'Scoping Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    $schedulingProject = Project::factory()->create([
        'title' => 'Scheduling Project',
        'status' => ProjectStatus::SCHEDULING,
    ]);

    livewire(BacklogList::class)
        ->set('statusFilter', 'all')
        ->assertSee('Scoping Project')
        ->assertSee('Scheduling Project');
});

test('displays project details in table columns', function () {
    $owner = User::factory()->create(['forenames' => 'John', 'surname' => 'Doe']);
    $technicalOwner = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Smith']);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Test Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    $project->scoping->update([
        'estimated_effort' => \App\Enums\EffortScale::MEDIUM,
    ]);

    $project->scheduling->update([
        'assigned_to' => $technicalOwner->id,
        'estimated_completion_date' => now()->addDays(30),
    ]);

    $project->ideation->update([
        'school_group' => 'Engineering',
    ]);

    livewire(BacklogList::class)
        ->assertSee($project->id)
        ->assertSee('Test Project')
        ->assertSee('John Doe')
        ->assertSee('Medium')
        ->assertSee('Jane Smith')
        ->assertSee(now()->addDays(30)->format('d/m/Y'))
        ->assertSee('Engineering');
});

test('pagination works correctly', function () {
    Project::factory()->count(30)->create();

    livewire(BacklogList::class)
        ->assertSee('pagination')
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2);
});

test('shows message when no projects match filter', function () {
    Project::factory()->create([
        'title' => 'Existing Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    livewire(BacklogList::class)
        ->set('search', 'Nonexistent Project Name')
        ->assertSee('No projects found matching your criteria');
});

test('ref number links to change on a page', function () {
    $project = Project::factory()->create(['title' => 'Test Project']);

    livewire(BacklogList::class)
        ->assertSeeHtml(route('portfolio.change-on-a-page', $project));
});

test('search and status filters work together', function () {
    Project::factory()->create([
        'title' => 'Migration in Scoping',
        'status' => ProjectStatus::SCOPING,
    ]);

    Project::factory()->create([
        'title' => 'Migration in Scheduling',
        'status' => ProjectStatus::SCHEDULING,
    ]);

    Project::factory()->create([
        'title' => 'Redesign in Scoping',
        'status' => ProjectStatus::SCOPING,
    ]);

    livewire(BacklogList::class)
        ->set('search', 'Migration')
        ->set('statusFilter', ProjectStatus::SCOPING->value)
        ->assertSee('Migration in Scoping')
        ->assertDontSee('Migration in Scheduling')
        ->assertDontSee('Redesign in Scoping');
});
