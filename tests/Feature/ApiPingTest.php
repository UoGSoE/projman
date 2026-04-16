<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects unauthenticated requests to the ping endpoint', function () {
    $this->getJson('/api/ping')
        ->assertUnauthorized();
});

it('responds with ok when called with a valid sanctum token', function () {
    $user = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $token = $user->createToken('PowerBI Test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/ping')
        ->assertOk()
        ->assertExactJson(['ok' => true]);
});

it('rejects a revoked token', function () {
    $user = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $token = $user->createToken('PowerBI Test')->plainTextToken;
    $user->tokens()->delete();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/ping')
        ->assertUnauthorized();
});
