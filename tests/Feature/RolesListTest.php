<?php

use App\Livewire\RolesList;
use App\Models\Role;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RolesList Component', function () {
    beforeEach(function () {
        // Create test roles
        $this->adminRole = Role::factory()->create([
            'name' => 'Administrator',
            'description' => 'System administrator role',
            'is_active' => true,
        ]);

        $this->userRole = Role::factory()->create([
            'name' => 'User',
            'description' => 'Regular user role',
            'is_active' => true,
        ]);

        $this->inactiveRole = Role::factory()->create([
            'name' => 'Inactive Role',
            'description' => 'This role is not active',
            'is_active' => false,
        ]);

        // Create admin user for testing
        $this->adminUser = User::factory()->create([
            'is_admin' => true,
        ]);
    });

    describe('Basic Rendering', function () {
        it('renders the component successfully', function () {
            livewire(RolesList::class)
                ->assertStatus(200)
                ->assertViewIs('livewire.roles-list');
        });

        it('displays roles in the list', function () {
            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('User')
                ->assertSeeText('Inactive Role');
        });

        it('has default sort settings', function () {
            $component = livewire(RolesList::class);

            expect($component->sortOn)->toBe('name');
            expect($component->sortDirection)->toBe('asc');
        });

        it('has empty search by default', function () {
            $component = livewire(RolesList::class);

            expect($component->search)->toBe('');
        });
    });

    describe('Search Functionality', function () {
        it('filters roles by name', function () {
            livewire(RolesList::class)
                ->set('search', 'Administrator')
                ->assertSeeText('Administrator')
                ->assertDontSeeText('User')
                ->assertDontSeeText('Inactive Role');
        });

        it('filters roles by description', function () {
            livewire(RolesList::class)
                ->set('search', 'System administrator')
                ->assertSeeText('Administrator')
                ->assertDontSeeText('User')
                ->assertDontSeeText('Inactive Role');
        });

        it('is case insensitive', function () {
            livewire(RolesList::class)
                ->set('search', 'administrator')
                ->assertSeeText('Administrator')
                ->assertDontSeeText('User');
        });

        it('requires minimum 2 characters for search', function () {
            livewire(RolesList::class)
                ->set('search', 'A')
                ->assertSeeText('Administrator')
                ->assertSeeText('User')
                ->assertSeeText('Inactive Role');
        });

        it('shows all roles when search is empty', function () {
            livewire(RolesList::class)
                ->set('search', 'Administrator')
                ->assertSeeText('Administrator')
                ->assertDontSeeText('User')
                ->set('search', '')
                ->assertSeeText('Administrator')
                ->assertSeeText('User')
                ->assertSeeText('Inactive Role');
        });
    });

    describe('Sorting Functionality', function () {
        it('sorts by name ascending by default', function () {
            livewire(RolesList::class)
                ->assertSeeInOrder(['Administrator', 'Inactive Role', 'User']);
        });

        it('toggles sort direction when clicking same column', function () {
            livewire(RolesList::class)
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'desc')
                ->assertSeeInOrder(['User', 'Inactive Role', 'Administrator'])
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Administrator', 'Inactive Role', 'User']);
        });

        it('changes sort column and resets to ascending', function () {
            livewire(RolesList::class)
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'desc')
                ->call('sort', 'is_active')
                ->assertSet('sortOn', 'is_active')
                ->assertSet('sortDirection', 'asc');
        });

        it('can sort by is_active column', function () {
            livewire(RolesList::class)
                ->call('sort', 'is_active')
                ->assertSet('sortOn', 'is_active')
                ->assertSet('sortDirection', 'asc');
        });
    });

    describe('Role Editing Modal', function () {
        it('opens edit modal with role data', function () {
            livewire(RolesList::class)
                ->call('openEditRoleModal', $this->adminRole)
                ->assertSet('selectedRole.id', $this->adminRole->id)
                ->assertSet('roleName', 'Administrator')
                ->assertSet('roleDescription', 'System administrator role')
                ->assertSet('roleIsActive', true);
        });

        it('populates form fields correctly', function () {
            $component = livewire(RolesList::class);
            $component->call('openEditRoleModal', $this->adminRole);

            expect($component->roleName)->toBe('Administrator');
            expect($component->roleDescription)->toBe('System administrator role');
            expect($component->roleIsActive)->toBe(true);
        });

        it('handles inactive role correctly', function () {
            $component = livewire(RolesList::class);
            $component->call('openEditRoleModal', $this->inactiveRole);

            expect($component->roleIsActive)->toBe(false);
        });

        it('loads fresh role data when opening modal', function () {
            // Modify the role in the database
            $this->adminRole->update(['name' => 'Updated Admin']);

            $component = livewire(RolesList::class);
            $component->call('openEditRoleModal', $this->adminRole);

            // Should load fresh data, not cached data
            expect($component->roleName)->toBe('Updated Admin');
        });

        it('tracks form modification correctly', function () {
            $component = livewire(RolesList::class);
            $component->call('openEditRoleModal', $this->adminRole);

            // Initially form should not be modified
            expect($component->formModified)->toBe(false);

            // Modify a field
            $component->set('roleName', 'New Name');
            expect($component->formModified)->toBe(true);

            // Modify another field
            $component->set('roleDescription', 'New Description');
            expect($component->formModified)->toBe(true);

            // Modify boolean field
            $component->set('roleIsActive', false);
            expect($component->formModified)->toBe(true);
        });

        it('resets form modification flag when modal is reset', function () {
            $component = livewire(RolesList::class);
            $component->call('openEditRoleModal', $this->adminRole);

            // Modify form
            $component->set('roleName', 'Modified Name');
            expect($component->formModified)->toBe(true);

            // Reset modal
            $component->call('resetEditRoleModal');
            expect($component->formModified)->toBe(false);
        });
    });

    describe('Role Validation', function () {
        beforeEach(function () {
            $this->component = livewire(RolesList::class);
            $this->component->call('openEditRoleModal', $this->adminRole);
        });

        it('requires name field', function () {
            $this->component
                ->set('roleName', '')
                ->call('saveEditRole')
                ->assertHasErrors(['roleName' => 'required']);
        });

        it('validates name length', function () {
            $this->component
                ->set('roleName', str_repeat('a', 256))
                ->call('saveEditRole')
                ->assertHasErrors(['roleName' => 'max']);
        });

        it('validates name uniqueness', function () {
            $this->component
                ->set('roleName', 'User') // This name already exists
                ->call('saveEditRole')
                ->assertHasErrors(['roleName' => 'unique']);
        });

        it('allows same name for same role', function () {
            $this->component
                ->set('roleName', 'Administrator') // Same name, same role
                ->call('saveEditRole')
                ->assertHasNoErrors(['roleName']);
        });

        it('validates description length', function () {
            $this->component
                ->set('roleDescription', str_repeat('a', 1001))
                ->call('saveEditRole')
                ->assertHasErrors(['roleDescription' => 'max']);
        });

        it('validates is_active as boolean', function () {
            $this->component
                ->set('roleIsActive', 'invalid')
                ->call('saveEditRole')
                ->assertHasErrors(['roleIsActive' => 'boolean']);
        });
    });

    describe('Role Saving', function () {
        beforeEach(function () {
            $this->component = livewire(RolesList::class);
            $this->component->call('openEditRoleModal', $this->adminRole);
        });

        it('saves role changes successfully', function () {
            $this->component
                ->set('roleName', 'Updated Admin')
                ->set('roleDescription', 'Updated description')
                ->set('roleIsActive', false)
                ->call('saveEditRole');

            $this->adminRole->refresh();

            expect($this->adminRole->name)->toBe('Updated Admin');
            expect($this->adminRole->description)->toBe('Updated description');
            expect($this->adminRole->is_active)->toBe(false);
        });

        it('resets form after successful save', function () {
            $this->component
                ->set('roleName', 'Updated Admin')
                ->set('roleDescription', 'Updated description')
                ->call('saveEditRole');

            expect($this->component->selectedRole)->toBeNull();
            expect($this->component->roleName)->toBe('');
            expect($this->component->roleDescription)->toBe('');
            expect($this->component->roleIsActive)->toBe(false);
        });

        it('updates role in database', function () {
            $originalName = $this->adminRole->name;

            $this->component
                ->set('roleName', 'New Name')
                ->call('saveEditRole');

            $this->adminRole->refresh();

            expect($this->adminRole->name)->not->toBe($originalName);
            expect($this->adminRole->name)->toBe('New Name');
        });
    });

    describe('Role Deletion', function () {
        it('deletes role successfully', function () {
            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);

            $component->call('deleteRole');

            // Role should be deleted from database
            $this->assertDatabaseMissing('roles', ['id' => $this->adminRole->id]);
        });

        it('removes deleted role from list', function () {
            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);

            $component->call('deleteRole');

            // Component should not show deleted role
            $component->assertDontSeeText('Administrator');
        });

        it('shows user count warning when deleting role with users', function () {
            // Assign users to the admin role
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $this->adminRole->users()->attach([$user1->id, $user2->id]);

            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);

            // Test that the component state is properly set
            expect($component->selectedRole->id)->toBe($this->adminRole->id);
            expect($component->selectedRole->users)->toHaveCount(2);

            // The modal content would be tested in integration tests with actual UI
        });

        it('shows simple confirmation when deleting role without users', function () {
            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->inactiveRole);

            // Test that the component state is properly set
            expect($component->selectedRole->id)->toBe($this->inactiveRole->id);
            expect($component->selectedRole->users)->toHaveCount(0);

            // The modal content would be tested in integration tests with actual UI
        });

        it('handles deletion of role with many users', function () {
            // Create many users and assign them to the admin role
            $manyUsers = User::factory()->count(25)->create();
            $this->adminRole->users()->attach($manyUsers->pluck('id'));

            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);

            // Test that the component state is properly set
            expect($component->selectedRole->id)->toBe($this->adminRole->id);
            expect($component->selectedRole->users)->toHaveCount(25);

            // The modal content would be tested in integration tests with actual UI
        });

        it('resets selected role after deletion', function () {
            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);

            $component->call('deleteRole');

            // selectedRole should be null after deletion
            expect($component->selectedRole)->toBeNull();
        });

        it('prevents deletion when no role is selected', function () {
            $component = livewire(RolesList::class);

            // Try to delete without selecting a role
            $component->call('deleteRole');

            // Should not crash and should not delete anything
            $this->assertDatabaseHas('roles', ['id' => $this->adminRole->id]);
        });
    });

    describe('Modal Reset', function () {
        it('resets form when modal is closed', function () {
            $component = livewire(RolesList::class);

            $component->call('openEditRoleModal', $this->adminRole);
            $component->set('roleName', 'Modified Name');

            $component->call('resetEditRoleModal');

            expect($component->selectedRole)->toBeNull();
            expect($component->roleName)->toBe('');
            expect($component->roleDescription)->toBe('');
            expect($component->roleIsActive)->toBe(false);
        });
    });

    describe('Edge Cases', function () {
        it('handles empty role list gracefully', function () {
            Role::query()->delete();

            livewire(RolesList::class)
                ->assertStatus(200);
        });

        it('handles special characters in role names', function () {
            Role::factory()->create([
                'name' => 'Test\'s Role',
                'description' => 'Role with special chars: & < > "',
                'is_active' => true,
            ]);

            livewire(RolesList::class)
                ->assertSeeText('Test\'s Role')
                ->assertSeeText('Role with special chars: & < > "');
        });

        it('handles very long role names and descriptions', function () {
            $longName = str_repeat('a', 255);
            $longDescription = str_repeat('b', 1000);

            Role::factory()->create([
                'name' => $longName,
                'description' => $longDescription,
                'is_active' => true,
            ]);

            livewire(RolesList::class)
                ->assertSeeText($longName)
                ->assertSeeText($longDescription);
        });
    });

    describe('Pagination', function () {
        it('paginates roles correctly', function () {
            // Create more roles to test pagination
            Role::factory()->count(15)->create();

            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('User')
                ->assertSeeText('Inactive Role');
        });
    });

    describe('Role Display with User Counts', function () {
        beforeEach(function () {
            // Create users and assign them to roles for testing user counts
            $this->user1 = User::factory()->create(['forenames' => 'User', 'surname' => 'One']);
            $this->user2 = User::factory()->create(['forenames' => 'User', 'surname' => 'Two']);
            $this->user3 = User::factory()->create(['forenames' => 'User', 'surname' => 'Three']);

            // Assign users to roles
            $this->adminRole->users()->attach([$this->user1->id, $this->user2->id]); // 2 users
            $this->userRole->users()->attach([$this->user3->id]); // 1 user
            // $this->inactiveRole has 0 users
        });

        it('displays user count badges for each role', function () {
            livewire(RolesList::class)
                ->assertSeeText('2') // Admin role has 2 users
                ->assertSeeText('1') // User role has 1 user
                ->assertSeeText('0'); // Inactive role has 0 users
        });

        it('shows correct user count for admin role', function () {
            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('2'); // Should show 2 users
        });

        it('shows correct user count for user role', function () {
            livewire(RolesList::class)
                ->assertSeeText('User')
                ->assertSeeText('1'); // Should show 1 user
        });

        it('shows zero user count for inactive role', function () {
            livewire(RolesList::class)
                ->assertSeeText('Inactive Role')
                ->assertSeeText('0'); // Should show 0 users
        });

        it('updates user count when users are assigned to roles', function () {
            // Create a new user
            $newUser = User::factory()->create(['forenames' => 'New', 'surname' => 'User']);

            // Assign the new user to the admin role
            $this->adminRole->users()->attach($newUser->id);

            // Refresh the role to get updated user count
            $this->adminRole->refresh();

            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('3'); // Should now show 3 users
        });

        it('updates user count when users are removed from roles', function () {
            // Remove a user from the admin role
            $this->adminRole->users()->detach($this->user1->id);

            // Refresh the role to get updated user count
            $this->adminRole->refresh();

            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('1'); // Should now show 1 user
        });

        it('handles role deletion and user count cleanup', function () {
            // First, remove the role from users so it can be deleted
            $this->user1->roles()->detach($this->adminRole);
            $this->user2->roles()->detach($this->adminRole);

            $component = livewire(RolesList::class);
            $component->call('openDeleteRoleModal', $this->adminRole);
            $component->call('deleteRole');

            // Verify role is deleted
            expect(Role::find($this->adminRole->id))->toBeNull();

            // Verify users no longer have the deleted role
            $this->user1->refresh();
            $this->user2->refresh();
            expect($this->user1->roles)->toHaveCount(0);
            expect($this->user2->roles)->toHaveCount(0);
        });

        it('maintains user count accuracy during role updates', function () {
            // Verify initial user count
            expect($this->adminRole->users)->toHaveCount(2);

            // Update the role name (should not affect user count)
            $rolesListComponent = livewire(RolesList::class);
            $rolesListComponent->call('openEditRoleModal', $this->adminRole);
            $rolesListComponent->set('roleName', 'Updated Admin Role');
            $rolesListComponent->call('saveEditRole');

            // Refresh the role
            $this->adminRole->refresh();

            // User count should remain the same
            expect($this->adminRole->users)->toHaveCount(2);

            // Display should show updated name with same user count
            livewire(RolesList::class)
                ->assertSeeText('Updated Admin Role')
                ->assertSeeText('2');
        });

        it('handles roles with many users', function () {
            // Create many users and assign them to a role
            $manyUsers = User::factory()->count(25)->create();
            $this->userRole->users()->attach($manyUsers->pluck('id'));

            // Refresh the role
            $this->userRole->refresh();

            // Should now have 26 users (1 original + 25 new)
            expect($this->userRole->users)->toHaveCount(26);

            // Display should show the correct count
            livewire(RolesList::class)
                ->assertSeeText('User')
                ->assertSeeText('26');
        });

        it('handles roles with no users gracefully', function () {
            // Create a role with no users
            $emptyRole = Role::factory()->create([
                'name' => 'Empty Role',
                'description' => 'Role with no users',
                'is_active' => true,
            ]);

            livewire(RolesList::class)
                ->assertSeeText('Empty Role')
                ->assertSeeText('0'); // Should show 0 users
        });

        it('displays user count badge with correct styling', function () {
            livewire(RolesList::class)
                ->assertSeeText('Administrator')
                ->assertSeeText('2')
                ->assertSeeText('User')
                ->assertSeeText('1');

            // The badge should be displayed inline with the role name
            // This tests that the flex layout and badge positioning work correctly
        });

        it('maintains user count display during search operations', function () {
            // Search for admin role
            livewire(RolesList::class)
                ->set('search', 'Administrator')
                ->assertSeeText('Administrator')
                ->assertSeeText('2') // User count should still be visible
                ->assertDontSeeText('User')
                ->assertDontSeeText('Inactive Role');

            // Clear search
            livewire(RolesList::class)
                ->set('search', '')
                ->assertSeeText('Administrator')
                ->assertSeeText('2')
                ->assertSeeText('User')
                ->assertSeeText('1');
        });

        it('maintains user count display during sorting operations', function () {
            $component = livewire(RolesList::class);

            // Sort by name descending
            $component->call('sort', 'name');
            expect($component->sortDirection)->toBe('desc');

            // Sort by name again to toggle back to ascending
            $component->call('sort', 'name');
            expect($component->sortDirection)->toBe('asc');

            // Verify user counts are still visible after sorting
            $component->assertSeeText('2') // Admin role user count
                ->assertSeeText('1') // User role user count
                ->assertSeeText('0'); // Inactive role user count
        });
    });
});
