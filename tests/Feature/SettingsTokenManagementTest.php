<?php

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('lets an admin create a named api token and renders it in the list', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($admin);

    livewire(Settings::class)
        ->set('newTokenName', 'PowerBI Production')
        ->call('createToken')
        ->assertHasNoErrors()
        ->assertSee('PowerBI Production');

    expect($admin->tokens()->count())->toBe(1);
    expect($admin->tokens()->first()->name)->toBe('PowerBI Production');
});

it('exposes the plaintext token only after creation', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($admin);

    livewire(Settings::class)
        ->assertSet('plainTextToken', null)
        ->set('newTokenName', 'PowerBI Production')
        ->call('createToken')
        ->assertSet('plainTextToken', fn ($value) => is_string($value) && ! empty($value));
});

it('lets an admin revoke a token', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($admin);
    $admin->createToken('Disposable');
    $tokenId = $admin->tokens()->first()->id;

    livewire(Settings::class)
        ->call('revokeToken', $tokenId);

    expect($admin->tokens()->count())->toBe(0);
});

it('rejects empty token names and creates no token', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($admin);

    livewire(Settings::class)
        ->set('newTokenName', '')
        ->call('createToken')
        ->assertHasErrors(['newTokenName']);

    expect($admin->tokens()->count())->toBe(0);
});

it('clears the plaintext token and pending name when the modal closes', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($admin);

    livewire(Settings::class)
        ->set('newTokenName', 'PowerBI Production')
        ->call('createToken')
        ->assertSet('plainTextToken', fn ($value) => is_string($value) && ! empty($value))
        ->call('resetTokenModal')
        ->assertSet('plainTextToken', null)
        ->assertSet('newTokenName', '');
});

it('blocks non-admin staff from reaching the token management page', function () {
    $staff = User::factory()->create(['is_admin' => false, 'is_staff' => true]);

    $this->actingAs($staff)
        ->get(route('settings'))
        ->assertForbidden();

    expect($staff->tokens()->count())->toBe(0);
});
