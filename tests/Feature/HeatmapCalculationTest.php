<?php

use App\Enums\AvailabilityForChange;
use App\Enums\EffortScale;
use App\Livewire\HeatMapViewer;
use App\Models\Project;
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

it('scales the same allocation against each availability band', function (AvailabilityForChange $availability, float $expectedUtilisation) {
    // A fixed allocation — Medium effort (10 person-days), one person, over 10 working
    // days — so the bare cost is exactly 1.0. Only the person's declared availability
    // changes between scenarios, which shows the result is purely that cost divided by
    // their availability, and that nothing caps it: at Minimal it reaches 500%.
    $user = User::factory()->make(['availability_for_change' => $availability]);

    expect(Project::calculatePerDayCost($user, effortDays: 10, peopleCount: 1, duration: 10))
        ->toEqualWithDelta($expectedUtilisation, 0.01);
})->with([
    'Full (100%) gives 100%' => [AvailabilityForChange::Full, 1.0],
    'Good (80%) gives 125%' => [AvailabilityForChange::Good, 1.25],
    'Moderate (60%) gives 167%' => [AvailabilityForChange::Moderate, 1.667],
    'Low (40%) gives 250%' => [AvailabilityForChange::Low, 2.5],
    'Minimal (20%) gives 500%' => [AvailabilityForChange::Minimal, 5.0],
]);

it('treats none availability as overloaded regardless of how small the allocation is', function () {
    // A deliberately tiny allocation — Small effort split ten ways over 200 working
    // days — would be a negligible cost for anyone else. With None availability it
    // still returns the maximum, so the cell always renders as overloaded.
    $user = User::factory()->make(['availability_for_change' => AvailabilityForChange::None]);

    expect(Project::calculatePerDayCost($user, effortDays: 5, peopleCount: 10, duration: 200))
        ->toBe(PHP_FLOAT_MAX);
});

it('counts the scheduling technical lead and change champion as team members', function () {
    // Medium effort (10 person-days) over 10 working days, all three people at Full
    // availability. With assigned_to, technical_lead_id and change_champion_id set,
    // three people share the effort: 10 / 3 / 10 / 1.0 = 0.333 each.
    $assigned = User::factory()->create(['is_staff' => true, 'availability_for_change' => AvailabilityForChange::Full]);
    $technicalLead = User::factory()->create(['is_staff' => true, 'availability_for_change' => AvailabilityForChange::Full]);
    $changeChampion = User::factory()->create(['is_staff' => true, 'availability_for_change' => AvailabilityForChange::Full]);

    $project = $this->createProject(['status' => 'scheduling']);
    $project->scoping->update(['estimated_effort' => EffortScale::MEDIUM]);

    $start = Carbon::parse('next monday');
    $project->scheduling->update([
        'assigned_to' => $assigned->id,
        'technical_lead_id' => $technicalLead->id,
        'change_champion_id' => $changeChampion->id,
        'estimated_start_date' => $start,
        'estimated_completion_date' => $start->copy()->addWeekdays(9),
    ]);

    $project = $project->fresh();

    expect($project->teamMemberIds())->toHaveCount(3)
        ->and($project->perDayCostForUser($technicalLead))->toEqualWithDelta(0.333, 0.01)
        ->and($project->perDayCostForUser($changeChampion))->toEqualWithDelta(0.333, 0.01);
});
