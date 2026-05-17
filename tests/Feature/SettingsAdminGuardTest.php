<?php

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Settings admin guard', function () {
    it('forbids a non-admin from creating an API token', function () {
        $nonAdmin = User::factory()->staff()->create();

        $this->actingAs($nonAdmin);

        livewire(Settings::class)
            ->set('newTokenName', 'Sneaky Token')
            ->call('createToken')
            ->assertForbidden();

        expect($nonAdmin->tokens()->count())->toBe(0);
    });

    it('forbids a non-admin from revoking an API token', function () {
        $nonAdmin = User::factory()->staff()->create();
        $token = $nonAdmin->createToken('Existing Token');

        $this->actingAs($nonAdmin);

        livewire(Settings::class)
            ->call('revokeToken', $token->accessToken->id)
            ->assertForbidden();

        expect($nonAdmin->fresh()->tokens()->count())->toBe(1);
    });
});
