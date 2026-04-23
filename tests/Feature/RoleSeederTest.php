<?php

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds every role referenced in projman config as active', function () {
    $this->seed(RoleSeeder::class);

    $expected = collect([
        'Admin',
        'Project Manager',
        'Work Package Assessor',
        'Service Lead',
        'Ideation Manager',
        'Feasibility Manager',
        'Scoping Manager',
        'Scheduling Manager',
        'Detailed Design Manager',
        'Development Manager',
        'Testing Manager',
        'Build Manager',
        'Deployment Manager',
        'Completed Manager',
        'Cancelled Manager',
        'Change Manager',
    ]);

    foreach ($expected as $name) {
        $role = Role::where('name', $name)->first();

        expect($role)->not->toBeNull("missing role: {$name}");
        expect($role->is_active)->toBeTrue();
        expect($role->description)->not->toBeEmpty();
    }
});

it('is idempotent when run twice', function () {
    $this->seed(RoleSeeder::class);
    $firstCount = Role::count();

    $this->seed(RoleSeeder::class);

    expect(Role::count())->toBe($firstCount);
});
