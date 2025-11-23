<?php

use App\Events\ProjectUpdated;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Minimal setup for ProjectCreated notification (fired when factory creates project)
    $adminRole = Role::factory()->create(['name' => 'Admin']);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);
});

it('records project history when event is fired with authenticated user', function () {
    // Arrange
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $initialHistoryCount = $project->history()->count();

    // Act
    event(new ProjectUpdated($project, 'Test message from authenticated user'));

    // Assert
    $project->refresh();
    expect($project->history()->count())->toBe($initialHistoryCount + 1);

    $latestHistory = $project->history()->latest()->first();
    expect($latestHistory->description)->toBe('Test message from authenticated user');
    expect($latestHistory->user_id)->toBe($user->id);
});

it('records project history when event is fired without authenticated user', function () {
    // Arrange
    $project = Project::factory()->create();

    // Ensure no user is authenticated
    auth()->logout();

    $initialHistoryCount = $project->history()->count();

    // Act
    event(new ProjectUpdated($project, 'Test message without user'));

    // Assert
    $project->refresh();
    expect($project->history()->count())->toBe($initialHistoryCount + 1);

    $latestHistory = $project->history()->latest()->first();
    expect($latestHistory->description)->toBe('Test message without user');
    expect($latestHistory->user_id)->toBeNull();
});
