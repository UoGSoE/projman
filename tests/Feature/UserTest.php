<?php

use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('flags a user as IT staff via isItStaff helper', function () {
    $user = User::factory()->create(['is_itstaff' => true]);

    expect($user->isItStaff())->toBeTrue();
});

it('flags a non-IT-staff user via isntItStaff helper', function () {
    $user = User::factory()->create(['is_itstaff' => false]);

    expect($user->isntItStaff())->toBeTrue();
    expect($user->isItStaff())->toBeFalse();
});

it('filters to IT staff only via the itStaff scope', function () {
    $itStaff = User::factory()->create(['is_itstaff' => true]);
    $otherStaff = User::factory()->create(['is_itstaff' => false]);

    $result = User::itStaff()->pluck('id');

    expect($result)->toContain($itStaff->id);
    expect($result)->not->toContain($otherStaff->id);
});

it('factory requester state produces university staff who are not IT staff', function () {
    $requester = User::factory()->requester()->create();

    expect($requester->is_staff)->toBeTrue();
    expect($requester->is_itstaff)->toBeFalse();
});

it('shows Admin on the type label when a user is both admin and IT staff', function () {
    $user = User::factory()->admin()->create();

    expect($user->typeLabel())->toBe('Admin');
});

it('returns only active IT assignments by default', function () {
    Event::fake([ProjectCreated::class]);

    $user = User::factory()->create();
    $owner = User::factory()->create();

    $active = Project::factory()->for($owner)->create(['status' => ProjectStatus::IDEATION]);
    $active->scheduling()->create(['cose_it_staff' => [$user->id]]);

    $completed = Project::factory()->for($owner)->create(['status' => ProjectStatus::COMPLETED]);
    $completed->scheduling()->create(['cose_it_staff' => [$user->id]]);

    $cancelled = Project::factory()->for($owner)->create(['status' => ProjectStatus::CANCELLED]);
    $cancelled->scheduling()->create(['cose_it_staff' => [$user->id]]);

    $assignments = $user->itAssignments();

    expect($assignments)->toHaveCount(1)
        ->and($assignments->first()->id)->toBe($active->id);
});

it('returns all IT assignments including completed and cancelled when asked', function () {
    Event::fake([ProjectCreated::class]);

    $user = User::factory()->create();
    $owner = User::factory()->create();

    $active = Project::factory()->for($owner)->create(['status' => ProjectStatus::IDEATION]);
    $active->scheduling()->create(['cose_it_staff' => [$user->id]]);

    $completed = Project::factory()->for($owner)->create(['status' => ProjectStatus::COMPLETED]);
    $completed->scheduling()->create(['cose_it_staff' => [$user->id]]);

    $assignments = $user->itAssignments(includeCompleted: true);

    expect($assignments->pluck('id'))->toContain($active->id, $completed->id);
});
