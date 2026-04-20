<?php

use App\Enums\Busyness;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds projects owned by non-staff requesters', function () {
    $this->seed(TestDataSeeder::class);

    expect(Project::count())->toBeGreaterThan(0);

    $staffIds = User::where('is_staff', true)->pluck('id');

    $staffOwnedProjects = Project::whereIn('user_id', $staffIds)->count();

    expect($staffOwnedProjects)->toBe(0);
});

it('creates a pool of non-staff requester users', function () {
    $this->seed(TestDataSeeder::class);

    $requesters = User::where('is_staff', false)
        ->where('is_admin', false)
        ->count();

    expect($requesters)->toBeGreaterThanOrEqual(15);
});

it('seeds a fixed pool of projects across the pipeline', function () {
    $this->seed(TestDataSeeder::class);

    $count = Project::count();

    expect($count)->toBeGreaterThanOrEqual(50);
    expect($count)->toBeLessThanOrEqual(80);
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
        expect($project->scheduling?->assigned_to)->not->toBeNull();
    }
});

it('sets staff busyness from assigned workload, not project ownership', function () {
    $this->seed(TestDataSeeder::class);

    $busyStaff = User::where('is_staff', true)
        ->whereNotIn('busyness_week_1', [Busyness::UNKNOWN->value])
        ->count();

    expect($busyStaff)->toBeGreaterThan(0);
});
