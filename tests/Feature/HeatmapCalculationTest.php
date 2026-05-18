<?php

use App\Enums\AvailabilityForChange;
use App\Enums\EffortScale;
use App\Livewire\HeatMapViewer;
use App\Models\User;
use App\Support\HeatmapCell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeAllProjectEvents();
});

it('calculates per-day cost for a single user assigned to a project', function () {
    // Alice has Minimal (20%) AFC, project is Medium (10 person-days),
    // duration is 15 working days, only Alice is assigned.
    // Expected: 10 / 1 / 15 / 0.2 = 3.333...
    $alice = User::factory()->create([
        'is_staff' => true,
        'availability_for_change' => AvailabilityForChange::Minimal,
    ]);

    $project = $this->createProject(['status' => 'scheduling']);
    $project->scoping->update(['estimated_effort' => EffortScale::MEDIUM]);

    $start = Carbon::parse('next monday');
    $project->scheduling->update([
        'assigned_to' => $alice->id,
        'estimated_start_date' => $start,
        'estimated_completion_date' => $start->copy()->addWeekdays(14),
    ]);

    expect($project->fresh()->perDayCostForUser($alice))->toEqualWithDelta(3.333, 0.01);
});

it('splits the cost equally when multiple people are allocated', function () {
    // Same Medium project on a 15-working-day duration, but now Alice (Minimal 20%)
    // shares it with Bob (Good 80%). Each carries half the load.
    // Alice: 10 / 2 / 15 / 0.2 = 1.667 → 167%
    // Bob:   10 / 2 / 15 / 0.8 = 0.417 → 42%
    $alice = User::factory()->create([
        'is_staff' => true,
        'availability_for_change' => AvailabilityForChange::Minimal,
    ]);
    $bob = User::factory()->create([
        'is_staff' => true,
        'availability_for_change' => AvailabilityForChange::Good,
    ]);

    $project = $this->createProject(['status' => 'scheduling']);
    $project->scoping->update(['estimated_effort' => EffortScale::MEDIUM]);

    $start = Carbon::parse('next monday');
    $project->scheduling->update([
        'assigned_to' => $alice->id,
        'cose_it_staff' => [$bob->id],
        'estimated_start_date' => $start,
        'estimated_completion_date' => $start->copy()->addWeekdays(14),
    ]);

    $project = $project->fresh();
    expect($project->perDayCostForUser($alice))->toEqualWithDelta(1.667, 0.01)
        ->and($project->perDayCostForUser($bob))->toEqualWithDelta(0.417, 0.01);
});

it('treats a user with zero availability as effectively overloaded by any allocation', function () {
    $unavailable = User::factory()->create([
        'is_staff' => true,
        'availability_for_change' => AvailabilityForChange::None,
    ]);

    $project = $this->createProject(['status' => 'scheduling']);
    $project->scoping->update(['estimated_effort' => EffortScale::SMALL]);

    $start = Carbon::parse('next monday');
    $project->scheduling->update([
        'assigned_to' => $unavailable->id,
        'estimated_start_date' => $start,
        'estimated_completion_date' => $start->copy()->addWeekdays(4),
    ]);

    // Cell will render as Black; we just assert the cost is comfortably above 1.0.
    expect($project->fresh()->perDayCostForUser($unavailable))->toBeGreaterThan(1.0);
});

it('returns zero when the project has no effort or no scheduling dates', function () {
    $alice = User::factory()->create([
        'is_staff' => true,
        'availability_for_change' => AvailabilityForChange::Moderate,
    ]);

    $unscoped = $this->createProject(['status' => 'feasibility']);
    $unscoped->scheduling->update(['assigned_to' => $alice->id]);

    expect($unscoped->fresh()->perDayCostForUser($alice))->toBe(0.0);
});

it('builds heatmap cells as HeatmapCell objects derived from active project allocations', function () {
    // Admin needed to hit the heatmap route.
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_staff' => true]));

    // Alice (Minimal 20%) solo on a Medium project for 15 weekdays.
    // Per-day cost = 10 / 1 / 15 / 0.2 = 3.333 → 333% → Black.
    $alice = User::factory()->create([
        'is_staff' => true,
        'surname' => 'Alice',
        'availability_for_change' => AvailabilityForChange::Minimal,
    ]);

    $project = $this->createProject(['status' => 'scheduling']);
    $project->scoping->update(['estimated_effort' => EffortScale::MEDIUM]);

    $start = Carbon::today()->isWeekend()
        ? Carbon::today()->next(Carbon::MONDAY)
        : Carbon::today();
    $project->scheduling->update([
        'assigned_to' => $alice->id,
        'estimated_start_date' => $start,
        'estimated_completion_date' => $start->copy()->addWeekdays(14),
    ]);

    $component = Livewire::test(HeatMapViewer::class);
    $staffData = $component->viewData('staff');
    $aliceData = $staffData->firstWhere('user.id', $alice->id);

    expect($aliceData['cells'])->toBeArray()
        ->and($aliceData['cells'][0])->toBeInstanceOf(HeatmapCell::class)
        ->and($aliceData['cells'][0]->colour())->toBe('bg-black');
});
