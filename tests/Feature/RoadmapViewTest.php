<?php

use App\Enums\ProjectStatus;
use App\Enums\ServiceFunction;
use App\Livewire\RoadmapView;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setupBaseNotificationRoles();
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->user);
});

it('renders the roadmap view successfully', function () {
    $this->get(route('portfolio.roadmap'))
        ->assertOk()
        ->assertSeeLivewire(RoadmapView::class)
        ->assertSee('Project Roadmap');
});

it('displays projects grouped by service function', function () {
    $infraUser = User::factory()->create(['service_function' => ServiceFunction::COLLEGE_INFRASTRUCTURE]);
    $researchUser = User::factory()->create(['service_function' => ServiceFunction::RESEARCH_COMPUTING]);

    $infraProject = Project::factory()->create([
        'user_id' => $infraUser->id,
        'title' => 'Infrastructure Project',
    ]);
    $researchProject = Project::factory()->create([
        'user_id' => $researchUser->id,
        'title' => 'Research Project',
    ]);

    $infraProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(3),
    ]);
    $researchProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(2),
    ]);

    Livewire::test(RoadmapView::class)
        ->assertSee('College Infrastructure')
        ->assertSee('Research Computing')
        ->assertSee('Infrastructure Project')
        ->assertSee('Research Project');
});

it('excludes projects without scheduling dates from timeline', function () {
    $user = User::factory()->create();
    $scheduledProject = Project::factory()->create(['user_id' => $user->id, 'title' => 'Scheduled Project']);
    $unscheduledProject = Project::factory()->create(['user_id' => $user->id, 'title' => 'Unscheduled Project']);

    $scheduledProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(2),
    ]);

    Livewire::test(RoadmapView::class)
        ->assertSee('Unscheduled Projects')
        ->assertSee('Unscheduled Project');
});

it('calculates BRAG status as black for completed projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => ProjectStatus::COMPLETED,
    ]);
    $project->scheduling()->update([
        'estimated_start_date' => now()->subMonths(3),
        'estimated_completion_date' => now()->subMonth(),
    ]);

    $component = Livewire::test(RoadmapView::class);
    $bragStatus = $component->instance()->calculateBRAG($project);

    expect($bragStatus)->toBe('black');
});

it('calculates BRAG status as red for overdue projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => ProjectStatus::DEVELOPMENT,
    ]);
    $project->scheduling()->update([
        'estimated_start_date' => now()->subMonths(2),
        'estimated_completion_date' => now()->subWeek(),
    ]);

    $component = Livewire::test(RoadmapView::class);
    $bragStatus = $component->instance()->calculateBRAG($project);

    expect($bragStatus)->toBe('red');
});

it('calculates BRAG status as amber for at-risk projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => ProjectStatus::TESTING,
    ]);
    $project->scheduling()->update([
        'estimated_start_date' => now()->subMonth(),
        'estimated_completion_date' => now()->addDays(10),
    ]);

    $component = Livewire::test(RoadmapView::class);
    $bragStatus = $component->instance()->calculateBRAG($project);

    expect($bragStatus)->toBe('amber');
});

it('calculates BRAG status as green for on-track projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'status' => ProjectStatus::SCOPING,
    ]);
    $project->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addDays(30), // Well beyond 14 day threshold
    ]);

    // Refresh the relationship to get updated dates
    $project->load('scheduling');

    $component = Livewire::test(RoadmapView::class);
    $bragStatus = $component->instance()->calculateBRAG($project);

    expect($bragStatus)->toBe('green');
});

it('displays month column headers correctly', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $project->scheduling()->update([
        'estimated_start_date' => now()->startOfMonth(),
        'estimated_completion_date' => now()->addMonths(2)->endOfMonth(),
    ]);

    Livewire::test(RoadmapView::class)
        ->assertSee(now()->format('M Y'))
        ->assertSee(now()->addMonth()->format('M Y'))
        ->assertSee(now()->addMonths(2)->format('M Y'));
});

it('calculates timeline start from earliest project date', function () {
    $user = User::factory()->create();

    $earlyProject = Project::factory()->create(['user_id' => $user->id]);
    $earlyProject->scheduling()->update([
        'estimated_start_date' => now()->subMonths(3),
        'estimated_completion_date' => now(),
    ]);

    $lateProject = Project::factory()->create(['user_id' => $user->id]);
    $lateProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(2),
    ]);

    $component = Livewire::test(RoadmapView::class);

    expect($component->instance()->timelineStart())
        ->toBeInstanceOf(Carbon::class)
        ->format('Y-m')->toBe(now()->subMonths(3)->format('Y-m'));
});

it('calculates timeline end from latest project date', function () {
    $user = User::factory()->create();

    $shortProject = Project::factory()->create(['user_id' => $user->id]);
    $shortProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonth(),
    ]);

    $longProject = Project::factory()->create(['user_id' => $user->id]);
    $longProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(6),
    ]);

    $component = Livewire::test(RoadmapView::class);

    expect($component->instance()->timelineEnd())
        ->toBeInstanceOf(Carbon::class)
        ->format('Y-m')->toBe(now()->addMonths(6)->format('Y-m'));
});

it('links project bars to change on a page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'title' => 'Test Project']);
    $project->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(2),
    ]);

    Livewire::test(RoadmapView::class)
        ->assertSee($project->title)
        ->assertSee(route('portfolio.change-on-a-page', $project));
});

it('shows project count for each service function', function () {
    $user = User::factory()->create(['service_function' => ServiceFunction::APPLICATIONS_DATA]);

    for ($i = 0; $i < 3; $i++) {
        $project = Project::factory()->create(['user_id' => $user->id]);
        $project->scheduling()->update([
            'estimated_start_date' => now(),
            'estimated_completion_date' => now()->addMonths(2),
        ]);
    }

    Livewire::test(RoadmapView::class)
        ->assertSee('Applications & Data')
        ->assertSee('(3)');
});

it('displays stubbed status boxes', function () {
    Livewire::test(RoadmapView::class)
        ->assertSee('Portfolio Health')
        ->assertSee('Delivery')
        ->assertSee('Budget')
        ->assertSee('Resource')
        ->assertSee('Dependencies');
});

it('handles empty roadmap gracefully', function () {
    Livewire::test(RoadmapView::class)
        ->assertSee('No scheduled projects');
});

it('excludes cancelled projects from roadmap', function () {
    $user = User::factory()->create();
    $cancelledProject = Project::factory()->create([
        'user_id' => $user->id,
        'status' => ProjectStatus::CANCELLED,
        'title' => 'Cancelled Project',
    ]);
    $cancelledProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(2),
    ]);

    Livewire::test(RoadmapView::class)
        ->assertDontSee('Cancelled Project');
});

it('comprehensive integration test', function () {
    $infraUser = User::factory()->create(['service_function' => ServiceFunction::COLLEGE_INFRASTRUCTURE]);
    $appsUser = User::factory()->create(['service_function' => ServiceFunction::APPLICATIONS_DATA]);

    $infraProject = Project::factory()->create([
        'user_id' => $infraUser->id,
        'title' => 'Infrastructure Upgrade',
        'status' => ProjectStatus::SCOPING,
    ]);
    $infraProject->scheduling()->update([
        'estimated_start_date' => now(),
        'estimated_completion_date' => now()->addMonths(4),
    ]);

    $overdueProject = Project::factory()->create([
        'user_id' => $appsUser->id,
        'title' => 'Overdue Migration',
        'status' => ProjectStatus::DEVELOPMENT,
    ]);
    $overdueProject->scheduling()->update([
        'estimated_start_date' => now()->subMonths(3),
        'estimated_completion_date' => now()->subWeek(),
    ]);

    $completedProject = Project::factory()->create([
        'user_id' => $appsUser->id,
        'title' => 'Completed Feature',
        'status' => ProjectStatus::COMPLETED,
    ]);
    $completedProject->scheduling()->update([
        'estimated_start_date' => now()->subMonths(2),
        'estimated_completion_date' => now()->subMonth(),
    ]);

    $unscheduledProject = Project::factory()->create([
        'user_id' => $infraUser->id,
        'title' => 'Future Planning',
    ]);

    $response = $this->get(route('portfolio.roadmap'));

    $response->assertOk()
        ->assertSee('Project Roadmap')
        ->assertSee('College Infrastructure')
        ->assertSee('Applications & Data')
        ->assertSee('Infrastructure Upgrade')
        ->assertSee('Overdue Migration')
        ->assertSee('Completed Feature')
        ->assertSee('Unscheduled Projects')
        ->assertSee('Future Planning')
        ->assertSee('Portfolio Health');
});
