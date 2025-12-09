<?php

use App\Enums\ProjectStatus;
use App\Livewire\BacklogList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fake all events and use createProject() for faster tests
    $this->fakeAllProjectEvents();
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->user);
});

test('backlog list page renders successfully', function () {
    $response = $this->get(route('portfolio.backlog'));

    $response->assertOk();
    $response->assertSeeLivewire(BacklogList::class);
});

test('displays active projects in backlog', function () {
    $activeProject = $this->createProject([
        'title' => 'Active Project One',
        'status' => ProjectStatus::SCOPING,
    ]);

    $completedProject = $this->createProject([
        'title' => 'Completed Project',
        'status' => ProjectStatus::COMPLETED,
    ]);

    $cancelledProject = $this->createProject([
        'title' => 'Cancelled Project',
        'status' => ProjectStatus::CANCELLED,
    ]);

    livewire(BacklogList::class)
        ->assertSee('Active Project One')
        ->assertDontSee('Completed Project')
        ->assertDontSee('Cancelled Project');
});

test('search filter works correctly', function () {
    $this->createProject(['title' => 'Data Migration Project']);
    $this->createProject(['title' => 'Website Redesign Project']);

    livewire(BacklogList::class)
        ->set('search', 'Migration')
        ->assertSee('Data Migration Project')
        ->assertDontSee('Website Redesign Project');
});

test('status filter works correctly', function () {
    $scopingProject = $this->createProject([
        'title' => 'Scoping Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    $schedulingProject = $this->createProject([
        'title' => 'Scheduling Project',
        'status' => ProjectStatus::SCHEDULING,
    ]);

    livewire(BacklogList::class)
        ->set('statusFilter', ProjectStatus::SCOPING->value)
        ->assertSee('Scoping Project')
        ->assertDontSee('Scheduling Project');
});

test('shows all statuses when filter is set to all', function () {
    $scopingProject = $this->createProject([
        'title' => 'Scoping Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    $schedulingProject = $this->createProject([
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

    $project = $this->createProject([
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
    for ($i = 0; $i < 30; $i++) {
        $this->createProject();
    }

    livewire(BacklogList::class)
        ->assertSee('pagination')
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2);
});

test('shows message when no projects match filter', function () {
    $this->createProject([
        'title' => 'Existing Project',
        'status' => ProjectStatus::SCOPING,
    ]);

    livewire(BacklogList::class)
        ->set('search', 'Nonexistent Project Name')
        ->assertSee('No projects found matching your criteria');
});

test('ref number links to change on a page', function () {
    $project = $this->createProject(['title' => 'Test Project']);

    livewire(BacklogList::class)
        ->assertSeeHtml(route('portfolio.change-on-a-page', $project));
});

test('search and status filters work together', function () {
    $this->createProject([
        'title' => 'Migration in Scoping',
        'status' => ProjectStatus::SCOPING,
    ]);

    $this->createProject([
        'title' => 'Migration in Scheduling',
        'status' => ProjectStatus::SCHEDULING,
    ]);

    $this->createProject([
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
