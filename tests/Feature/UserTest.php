<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
