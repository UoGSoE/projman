<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\HasApiTokens;

uses(RefreshDatabase::class);

describe('Sanctum foundation', function () {
    it('has the HasApiTokens trait on the User model', function () {
        expect(class_uses_recursive(User::class))
            ->toContain(HasApiTokens::class);
    });

    it('can create a named api token for a user', function () {
        $user = User::factory()->create();

        $newToken = $user->createToken('PowerBI Production');

        expect($newToken->plainTextToken)->toBeString()->not->toBeEmpty();
        expect($user->tokens()->count())->toBe(1);
        expect($user->tokens()->first()->name)->toBe('PowerBI Production');
    });

    it('can revoke an api token', function () {
        $user = User::factory()->create();
        $user->createToken('Disposable');

        $user->tokens()->delete();

        expect($user->tokens()->count())->toBe(0);
    });
});
