<?php

use App\Livewire\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('User List Display', function () {
    it('shows users with their roles', function () {
        $userWithRoles = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Doe']);
        $role = Role::factory()->create(['name' => 'Manager', 'is_active' => true]);
        $userWithRoles->roles()->attach($role);

        livewire(UserList::class)
            ->assertSeeText('Jane')
            ->assertSeeText('Doe')
            ->assertSeeText('Manager');
    });

    it('shows "No roles" for users without roles', function () {
        User::factory()->create(['forenames' => 'John', 'surname' => 'Smith']);

        livewire(UserList::class)
            ->assertSeeText('John')
            ->assertSeeText('Smith')
            ->assertSeeText('No roles');
    });

    it('shows +N badge when user has multiple roles', function () {
        $user = User::factory()->create();
        $roles = Role::factory()->count(3)->create(['is_active' => true]);
        $user->roles()->attach($roles);

        livewire(UserList::class)
            ->assertSeeText($roles->first()->name)
            ->assertSeeText('+2');
    });

    it('shows admin badge for admin users', function () {
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);

        livewire(UserList::class)
            ->assertSeeTextInOrder(['Admin', 'User']);
    });
});

describe('Role Assignment Modal', function () {
    it('opens with user current roles selected', function () {
        $user = User::factory()->create();
        $assignedRole = Role::factory()->create(['name' => 'Assigned Role', 'is_active' => true]);
        $user->roles()->attach($assignedRole);

        $component = livewire(UserList::class)
            ->call('openChangeUserRoleModal', $user);

        expect($component->userRoles)->toContain('Assigned Role');
        expect($component->selectedUser->id)->toBe($user->id);
    });

    it('shows only active roles as available options', function () {
        $user = User::factory()->create();
        $activeRole = Role::factory()->create(['name' => 'Active Role', 'is_active' => true]);
        $inactiveRole = Role::factory()->create(['name' => 'Inactive Role', 'is_active' => false]);

        $component = livewire(UserList::class)
            ->call('openChangeUserRoleModal', $user);

        $availableNames = $component->availableRoles->pluck('name')->toArray();
        expect($availableNames)->toContain('Active Role');
        expect($availableNames)->not->toContain('Inactive Role');
    });
});

describe('Role Assignment', function () {
    it('saves assigned roles to database', function () {
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['name' => 'Role One', 'is_active' => true]);
        $role2 = Role::factory()->create(['name' => 'Role Two', 'is_active' => true]);

        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $user)
            ->set('userRoles', ['Role One', 'Role Two'])
            ->call('saveUserRoles');

        $user->refresh();
        expect($user->roles)->toHaveCount(2);
        expect($user->roles->pluck('name')->toArray())->toContain('Role One', 'Role Two');
    });

    it('removes roles when unassigned', function () {
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['name' => 'Keep This', 'is_active' => true]);
        $role2 = Role::factory()->create(['name' => 'Remove This', 'is_active' => true]);
        $user->roles()->attach([$role1->id, $role2->id]);

        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $user)
            ->set('userRoles', ['Keep This'])
            ->call('saveUserRoles');

        $user->refresh();
        expect($user->roles)->toHaveCount(1);
        expect($user->roles->first()->name)->toBe('Keep This');
    });

    it('allows removing all roles', function () {
        $user = User::factory()->create();
        $role = Role::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $user)
            ->set('userRoles', [])
            ->call('saveUserRoles');

        $user->refresh();
        expect($user->roles)->toHaveCount(0);
    });
});

describe('Admin Toggle', function () {
    it('toggles user admin status', function () {
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin);

        livewire(UserList::class)
            ->call('toggleAdmin', $regularUser);

        $regularUser->refresh();
        expect($regularUser->is_admin)->toBeTrue();

        livewire(UserList::class)
            ->call('toggleAdmin', $regularUser);

        $regularUser->refresh();
        expect($regularUser->is_admin)->toBeFalse();
    });

    it('requires admin permission to toggle', function () {
        $nonAdmin = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create(['is_admin' => false]);

        $this->actingAs($nonAdmin);

        livewire(UserList::class)
            ->call('toggleAdmin', $targetUser)
            ->assertForbidden();

        $targetUser->refresh();
        expect($targetUser->is_admin)->toBeFalse();
    });
});

describe('Search and Sort', function () {
    it('filters users by search term', function () {
        User::factory()->create(['forenames' => 'Alice', 'surname' => 'Smith']);
        User::factory()->create(['forenames' => 'Bob', 'surname' => 'Jones']);

        livewire(UserList::class)
            ->set('search', 'Alice')
            ->assertSeeText('Alice')
            ->assertDontSeeText('Bob');
    });

    it('sorts users by column', function () {
        User::factory()->create(['forenames' => 'Zebra']);
        User::factory()->create(['forenames' => 'Apple']);

        // Sort by forenames (not the default column)
        livewire(UserList::class)
            ->call('sort', 'forenames')
            ->assertSeeTextInOrder(['Apple', 'Zebra']);
    });
});
