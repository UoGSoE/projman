<?php

use App\Livewire\RolesList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Role List Display', function () {
    it('renders the component and displays roles', function () {
        Role::factory()->create(['name' => 'Administrator']);
        Role::factory()->create(['name' => 'Editor']);

        livewire(RolesList::class)
            ->assertStatus(200)
            ->assertSeeText('Administrator')
            ->assertSeeText('Editor');
    });

    it('shows user count for each role', function () {
        $role = Role::factory()->create(['name' => 'Test Role']);
        $users = User::factory()->count(3)->create();
        $role->users()->attach($users);

        livewire(RolesList::class)
            ->assertSeeText('Test Role')
            ->assertSeeText('3');
    });

    it('shows active status for roles', function () {
        Role::factory()->create(['name' => 'Active Role', 'is_active' => true]);
        Role::factory()->create(['name' => 'Inactive Role', 'is_active' => false]);

        livewire(RolesList::class)
            ->assertSeeText('Active Role')
            ->assertSeeText('Inactive Role')
            ->assertSeeText('Yes')
            ->assertSeeText('No');
    });
});

describe('Search', function () {
    it('filters roles by name', function () {
        Role::factory()->create(['name' => 'Administrator']);
        Role::factory()->create(['name' => 'Editor']);

        livewire(RolesList::class)
            ->set('search', 'Admin')
            ->assertSeeText('Administrator')
            ->assertDontSeeText('Editor');
    });

    it('filters roles by description', function () {
        Role::factory()->create(['name' => 'Admin', 'description' => 'System administrator']);
        Role::factory()->create(['name' => 'Editor', 'description' => 'Content editor']);

        livewire(RolesList::class)
            ->set('search', 'Content')
            ->assertSeeText('Editor')
            ->assertDontSeeText('Admin');
    });

    it('requires minimum 2 characters', function () {
        Role::factory()->create(['name' => 'Administrator']);
        Role::factory()->create(['name' => 'Editor']);

        livewire(RolesList::class)
            ->set('search', 'A')
            ->assertSeeText('Administrator')
            ->assertSeeText('Editor');
    });
});

describe('Sorting', function () {
    it('sorts by name ascending by default', function () {
        Role::factory()->create(['name' => 'Zebra']);
        Role::factory()->create(['name' => 'Apple']);

        livewire(RolesList::class)
            ->assertSeeTextInOrder(['Apple', 'Zebra']);
    });

    it('toggles sort direction when clicking same column', function () {
        Role::factory()->create(['name' => 'Zebra']);
        Role::factory()->create(['name' => 'Apple']);

        livewire(RolesList::class)
            ->call('sort', 'name')
            ->assertSeeTextInOrder(['Zebra', 'Apple'])
            ->call('sort', 'name')
            ->assertSeeTextInOrder(['Apple', 'Zebra']);
    });
});

describe('Create Role', function () {
    it('creates a new role', function () {
        livewire(RolesList::class)
            ->call('openCreateRoleModal')
            ->set('roleName', 'New Role')
            ->set('roleDescription', 'A brand new role')
            ->set('roleIsActive', true)
            ->call('saveEditRole');

        expect(Role::where('name', 'New Role')->exists())->toBeTrue();

        $role = Role::where('name', 'New Role')->first();
        expect($role->description)->toBe('A brand new role');
        expect($role->is_active)->toBeTrue();
    });

    it('validates required fields when creating', function () {
        livewire(RolesList::class)
            ->call('openCreateRoleModal')
            ->set('roleName', '')
            ->set('roleDescription', '')
            ->call('saveEditRole')
            ->assertHasErrors(['roleName', 'roleDescription']);
    });

    it('validates name uniqueness when creating', function () {
        Role::factory()->create(['name' => 'Existing Role']);

        livewire(RolesList::class)
            ->call('openCreateRoleModal')
            ->set('roleName', 'Existing Role')
            ->set('roleDescription', 'Some description')
            ->call('saveEditRole')
            ->assertHasErrors(['roleName']);
    });
});

describe('Edit Role', function () {
    it('opens edit modal with role data', function () {
        $role = Role::factory()->create([
            'name' => 'Test Role',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $component = livewire(RolesList::class)
            ->call('openEditRoleModal', $role);

        expect($component->roleName)->toBe('Test Role');
        expect($component->roleDescription)->toBe('Test description');
        expect($component->roleIsActive)->toBeTrue();
    });

    it('saves role changes to database', function () {
        $role = Role::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        livewire(RolesList::class)
            ->call('openEditRoleModal', $role)
            ->set('roleName', 'Updated Name')
            ->set('roleDescription', 'Updated description')
            ->set('roleIsActive', false)
            ->call('saveEditRole');

        $role->refresh();
        expect($role->name)->toBe('Updated Name');
        expect($role->description)->toBe('Updated description');
        expect($role->is_active)->toBeFalse();
    });

    it('allows keeping the same name when editing', function () {
        $role = Role::factory()->create(['name' => 'My Role']);

        livewire(RolesList::class)
            ->call('openEditRoleModal', $role)
            ->set('roleName', 'My Role')
            ->set('roleDescription', 'Updated description')
            ->call('saveEditRole')
            ->assertHasNoErrors();

        $role->refresh();
        expect($role->description)->toBe('Updated description');
    });

    it('prevents using another roles name', function () {
        Role::factory()->create(['name' => 'Other Role']);
        $role = Role::factory()->create(['name' => 'My Role']);

        livewire(RolesList::class)
            ->call('openEditRoleModal', $role)
            ->set('roleName', 'Other Role')
            ->call('saveEditRole')
            ->assertHasErrors(['roleName']);
    });
});

describe('Delete Role', function () {
    it('deletes a role without users', function () {
        $role = Role::factory()->create(['name' => 'Delete Me']);

        livewire(RolesList::class)
            ->call('openDeleteRoleModal', $role)
            ->call('deleteRole');

        expect(Role::where('name', 'Delete Me')->exists())->toBeFalse();
    });

    it('prevents deleting a role with users assigned', function () {
        $role = Role::factory()->create(['name' => 'Has Users']);
        $user = User::factory()->create();
        $role->users()->attach($user);

        livewire(RolesList::class)
            ->call('openDeleteRoleModal', $role)
            ->call('deleteRole');

        // Role should still exist
        expect(Role::where('name', 'Has Users')->exists())->toBeTrue();
    });

    it('shows warning when role has users', function () {
        $role = Role::factory()->create(['name' => 'Popular Role']);
        $users = User::factory()->count(5)->create();
        $role->users()->attach($users);

        $component = livewire(RolesList::class)
            ->call('openDeleteRoleModal', $role);

        expect($component->selectedRole->users)->toHaveCount(5);
    });
});
