<?php

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the settings page for admins', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);

    $this->actingAs($admin)
        ->get(route('settings'))
        ->assertOk()
        ->assertSeeLivewire(Settings::class);
});

it('forbids non-admin staff from viewing settings', function () {
    $staff = User::factory()->create(['is_admin' => false, 'is_staff' => true]);

    $this->actingAs($staff)
        ->get(route('settings'))
        ->assertForbidden();
});

it('redirects guests away from settings', function () {
    $this->get(route('settings'))
        ->assertRedirect(route('login'));
});
