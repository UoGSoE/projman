<?php

use App\Enums\Busyness;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds projects owned by non-IT-staff requesters', function () {
    $this->seed(TestDataSeeder::class);

    expect(Project::count())->toBeGreaterThan(0);

    $itStaffIds = User::itStaff()->pluck('id');

    $itStaffOwnedProjects = Project::whereIn('user_id', $itStaffIds)->count();

    expect($itStaffOwnedProjects)->toBe(0);
});

it('creates a pool of non-IT-staff requester users', function () {
    $this->seed(TestDataSeeder::class);

    $requesters = User::query()
        ->where('is_staff', true)
        ->where('is_itstaff', false)
        ->where('is_admin', false)
        ->count();

    expect($requesters)->toBeGreaterThanOrEqual(15);
});

it('seeds a fixed pool of projects across the pipeline', function () {
    $this->seed(TestDataSeeder::class);

    $count = Project::count();

    expect($count)->toBeGreaterThanOrEqual(50);
    expect($count)->toBeLessThanOrEqual(80);

    // Projects are spread across the whole pipeline, not bunched in one stage.
    $stages = [
        ProjectStatus::IDEATION,
        ProjectStatus::FEASIBILITY,
        ProjectStatus::SCOPING,
        ProjectStatus::SCHEDULING,
        ProjectStatus::DETAILED_DESIGN,
        ProjectStatus::DEVELOPMENT,
        ProjectStatus::TESTING,
        ProjectStatus::DEPLOYED,
        ProjectStatus::COMPLETED,
        ProjectStatus::CANCELLED,
    ];

    foreach ($stages as $stage) {
        expect(Project::where('status', $stage)->count())->toBeGreaterThan(0);
    }
});

it('does not allocate staff to projects that have not reached that stage', function () {
    $this->seed(TestDataSeeder::class);

    $ideationProjects = Project::where('status', ProjectStatus::IDEATION)
        ->with(['scheduling', 'detailedDesign', 'development'])
        ->get();

    expect($ideationProjects)->not->toBeEmpty();

    foreach ($ideationProjects as $project) {
        expect($project->scheduling?->assigned_to)->toBeNull();
        expect($project->detailedDesign?->designed_by)->toBeNull();
        expect($project->development?->lead_developer)->toBeNull();
    }
});

it('allocates staff to projects from the scheduling stage onwards', function () {
    $this->seed(TestDataSeeder::class);

    $scheduledProjects = Project::whereIn('status', [
        ProjectStatus::SCHEDULING,
        ProjectStatus::DETAILED_DESIGN,
        ProjectStatus::DEVELOPMENT,
        ProjectStatus::TESTING,
        ProjectStatus::DEPLOYED,
    ])
        ->with('scheduling')
        ->get();

    expect($scheduledProjects)->not->toBeEmpty();

    foreach ($scheduledProjects as $project) {
        expect($project->scheduling)->not->toBeNull();
        expect($project->scheduling->assigned_to)->not->toBeNull();
    }
});

it('sets staff busyness from assigned workload, not project ownership', function () {
    $this->seed(TestDataSeeder::class);

    $itStaff = User::itStaff()->get();

    // Each IT staffer's week-1 busyness is derived precisely from their active assignment count.
    foreach ($itStaff as $member) {
        $expected = Busyness::fromProjectCount($member->activeAssignedProjectCount());
        expect($member->busyness_week_1)->toBe($expected);
    }

    // Sanity: the workload isn't all zero - at least some staff register as busy.
    expect($itStaff->contains(fn ($m) => $m->busyness_week_1 !== Busyness::UNKNOWN))->toBeTrue();

    // Ownership does not create busyness: requesters own projects but are never assigned,
    // so their week-1 busyness stays UNKNOWN.
    $requestersWhoOwnProjects = User::query()
        ->where('is_itstaff', false)
        ->where('is_admin', false)
        ->whereHas('projects')
        ->get();

    expect($requestersWhoOwnProjects)->not->toBeEmpty();
    foreach ($requestersWhoOwnProjects as $requester) {
        expect($requester->busyness_week_1)->toBe(Busyness::UNKNOWN);
    }
});
