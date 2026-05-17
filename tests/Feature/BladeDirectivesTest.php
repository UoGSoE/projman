<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

describe('@adminOrITStaff directive', function () {
    it('returns false for a guest user without erroring', function () {
        expect(Blade::check('adminOrITStaff'))->toBeFalse();
    });

    it('returns true for an admin user', function () {
        $this->actingAs(User::factory()->admin()->create());
        expect(Blade::check('adminOrITStaff'))->toBeTrue();
    });

    it('returns true for an IT staff user', function () {
        $this->actingAs(User::factory()->staff()->create());
        expect(Blade::check('adminOrITStaff'))->toBeTrue();
    });

    it('returns false for a regular requester', function () {
        $this->actingAs(User::factory()->requester()->create());
        expect(Blade::check('adminOrITStaff'))->toBeFalse();
    });
});
