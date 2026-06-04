<?php

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds every role referenced in projman config as active', function () {
    $this->seed(RoleSeeder::class);

    // Derived from config so the test cannot drift from the roles the notification system
    // actually references: each event's `roles`, plus ProjectStageChange's `stage_roles`.
    $configuredRoles = collect(config('projman.notifications'))
        ->flatMap(fn ($definition) => collect($definition['roles'] ?? [])
            ->merge(collect($definition['stage_roles'] ?? [])->flatten()))
        ->unique()
        ->values();

    expect($configuredRoles)->not->toBeEmpty();

    foreach ($configuredRoles as $name) {
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
