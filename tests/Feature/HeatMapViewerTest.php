<?php

use App\Livewire\HeatMapViewer;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_admin' => true,
        'surname' => 'AdminUser',
    ]);

    // Attach Admin role so ensureProjectCreatedRoles() doesn't create duplicate admin user
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->user->roles()->attach($adminRole);

    // Fake notifications for this test suite (doesn't test notification behavior)
    // Must be called AFTER user creation so ensureProjectCreatedRoles() sees this user
    $this->fakeNotifications();

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
    $activeProject = Project::factory()->create([
        'title' => 'Active Test Project',
        'status' => 'scoping',
    ]);

    $cancelledProject = Project::factory()->create([
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

it('provides 10 upcoming working days', function () {
    // Act
    $component = Livewire::test(HeatMapViewer::class);

    // Assert
    $days = $component->viewData('days');
    expect($days)->toHaveCount(10);

    // All days should be weekdays
    foreach ($days as $day) {
        expect($day->isWeekday())->toBeTrue();
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
