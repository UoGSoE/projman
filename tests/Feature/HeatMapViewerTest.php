<?php

use App\Enums\Busyness;
use App\Livewire\HeatMapViewer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeAllProjectEvents();

    $this->user = User::factory()->create([
        'is_admin' => true,
        'surname' => 'AdminUser',
    ]);

    $this->actingAs($this->user);
});

it('displays the heatmap page with component', function () {
    $this->get(route('project.heatmap'))
        ->assertOk()
        ->assertSeeLivewire(HeatMapViewer::class);
});

it('provides staff sorted alphabetically by surname', function () {
    // Arrange
    $staffAdams = User::factory()->create([
        'is_staff' => true,
        'forenames' => 'Zara',
        'surname' => 'Adams',
    ]);

    $staffSmith = User::factory()->create([
        'is_staff' => true,
        'forenames' => 'Bob',
        'surname' => 'Smith',
    ]);

    $staffJones = User::factory()->create([
        'is_staff' => true,
        'forenames' => 'Alice',
        'surname' => 'Jones',
    ]);

    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert - staff should be in alphabetical order
    $staff = $component->viewData('staff');
    // Four users: admin from beforeEach + 3 staff created above
    expect($staff)->toHaveCount(4);

    expect($staff[0]['user']->surname)->toBe($staffAdams->surname);
    expect($staff[1]['user']->surname)->toBe('AdminUser');
    expect($staff[2]['user']->surname)->toBe($staffJones->surname);
    expect($staff[3]['user']->surname)->toBe($staffSmith->surname);
});

it('provides active projects but excludes cancelled projects', function () {
    // Arrange
    $activeProject = $this->createProject([
        'title' => 'Active Test Project',
        'status' => 'scoping',
    ]);

    $cancelledProject = $this->createProject([
        'title' => 'Cancelled Project',
        'status' => 'cancelled',
    ]);

    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert
    $activeProjects = $component->viewData('activeProjects');
    expect($activeProjects)->toHaveCount(1);
    expect($activeProjects[0]->id)->toBe($activeProject->id);
});

it('provides 10 buckets in days view by default', function () {
    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert
    $buckets = $component->viewData('buckets');
    expect($buckets)->toHaveCount(10);

    // All buckets should have label/sublabel/start/end
    foreach ($buckets as $bucket) {
        expect($bucket)->toHaveKeys(['label', 'sublabel', 'start', 'end']);
        expect($bucket['start']->isWeekday())->toBeTrue();
    }
});

it('includes busyness data for each staff member', function () {
    // Arrange
    User::factory()->create([
        'is_staff' => true,
        'busyness_week_1' => 30, // LOW
        'busyness_week_2' => 60, // MEDIUM
    ]);

    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert
    $staff = $component->viewData('staff');
    expect($staff[0])->toHaveKey('busyness');
    expect($staff[0]['busyness'])->toHaveCount(10);
});

it('defaults to days view mode', function () {
    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert
    expect($component->get('viewMode'))->toBe('days');
    expect($component->viewData('viewMode'))->toBe('days');
});

it('can switch to weeks view mode', function () {
    // Act
    $component = Livewire::test(HeatMapViewer::class)
        ->set('viewMode', 'weeks');

    // Assert
    expect($component->get('viewMode'))->toBe('weeks');
    $buckets = $component->viewData('buckets');
    expect($buckets)->toHaveCount(10);

    // Week buckets should have W prefix in label
    foreach ($buckets as $bucket) {
        expect($bucket['label'])->toStartWith('W');
    }
});

it('can switch to months view mode', function () {
    // Act
    $component = Livewire::test(HeatMapViewer::class)
        ->set('viewMode', 'months');

    // Assert
    expect($component->get('viewMode'))->toBe('months');
    $buckets = $component->viewData('buckets');
    expect($buckets)->toHaveCount(10);

    // Month buckets should have 3-letter month abbreviation
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    foreach ($buckets as $bucket) {
        expect($monthNames)->toContain($bucket['label']);
    }
});

it('calculates busyness from project assignments in weeks view', function () {
    // Arrange
    $staff = User::factory()->create([
        'is_staff' => true,
    ]);

    $project = $this->createProject([
        'title' => 'Assigned Project',
        'status' => 'scheduling',
    ]);

    // Assign the staff member to the project with dates that overlap the first week
    $project->scheduling->update([
        'assigned_to' => $staff->id,
        'estimated_start_date' => now()->startOfWeek(),
        'estimated_completion_date' => now()->endOfWeek(),
    ]);

    // Act
    $component = Livewire::test(HeatMapViewer::class)
        ->set('viewMode', 'weeks');

    // Assert - staff should have LOW busyness for first week (1 project)
    $staffData = $component->viewData('staff');
    $assignedStaff = $staffData->firstWhere('user.id', $staff->id);

    expect($assignedStaff['busyness'][0])->toBe(Busyness::LOW);
});

it('persists view mode in URL', function () {
    // Arrange & Act
    $this->get(route('project.heatmap', ['viewMode' => 'months']))
        ->assertOk();

    $component = Livewire::test(HeatMapViewer::class)
        ->set('viewMode', 'months');

    // Assert
    expect($component->get('viewMode'))->toBe('months');
});

it('can filter staff by name using the name filter', function () {
    // Arrange
    $staffAdams = User::factory()->create([
        'is_staff' => true,
        'forenames' => 'Zara',
        'surname' => 'Adams',
    ]);

    $staffSmith = User::factory()->create([
        'is_staff' => true,
        'forenames' => 'Bob',
        'surname' => 'Smith',
    ]);

    // Act - filter to only show Adams
    $component = Livewire::test(HeatMapViewer::class)
        ->set('nameFilter', [$staffAdams->id]);

    // Assert - only Adams should be in the filtered staff list
    $staff = $component->viewData('staff');
    expect($staff)->toHaveCount(1);
    expect($staff->first()['user']->id)->toBe($staffAdams->id);
});

it('shows all staff when name filter is empty', function () {
    // Arrange
    User::factory()->create(['is_staff' => true]);
    User::factory()->create(['is_staff' => true]);

    // Act - no filter applied
    $component = Livewire::test(HeatMapViewer::class);

    // Assert - all staff should be shown (admin from beforeEach + 2 created)
    $staff = $component->viewData('staff');
    expect($staff)->toHaveCount(3);
});

it('can filter multiple staff members', function () {
    // Arrange
    $staffAdams = User::factory()->create([
        'is_staff' => true,
        'surname' => 'Adams',
    ]);

    $staffSmith = User::factory()->create([
        'is_staff' => true,
        'surname' => 'Smith',
    ]);

    $staffJones = User::factory()->create([
        'is_staff' => true,
        'surname' => 'Jones',
    ]);

    // Act - filter to show Adams and Jones
    $component = Livewire::test(HeatMapViewer::class)
        ->set('nameFilter', [$staffAdams->id, $staffJones->id]);

    // Assert - only Adams and Jones should be shown
    $staff = $component->viewData('staff');
    expect($staff)->toHaveCount(2);

    $surnames = $staff->pluck('user.surname')->toArray();
    expect($surnames)->toContain('Adams');
    expect($surnames)->toContain('Jones');
    expect($surnames)->not->toContain('Smith');
});

it('provides all staff for the pillbox options regardless of filter', function () {
    // Arrange
    $staffAdams = User::factory()->create(['is_staff' => true, 'surname' => 'Adams']);
    $staffSmith = User::factory()->create(['is_staff' => true, 'surname' => 'Smith']);

    // Act - filter to only show Adams
    $component = Livewire::test(HeatMapViewer::class)
        ->set('nameFilter', [$staffAdams->id]);

    // Assert - allStaff should contain all staff (for pillbox options)
    $allStaff = $component->viewData('allStaff');
    expect($allStaff)->toHaveCount(3); // admin + 2 created
});
