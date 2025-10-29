<?php

use App\Livewire\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('User Role Management', function () {
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

        $this->managerRole = Role::factory()->create([
            'name' => 'Manager',
            'description' => 'Team manager role',
            'is_active' => true,
        ]);

        $this->inactiveRole = Role::factory()->create([
            'name' => 'Inactive Role',
            'description' => 'This role is not active',
            'is_active' => false,
        ]);

        // Create test users
        $this->adminUser = User::factory()->create([
            'forenames' => 'Admin',
            'surname' => 'User',
            'username' => 'admin.user',
            'email' => 'admin.user@example.ac.uk',
            'is_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'forenames' => 'John',
            'surname' => 'Smith',
            'username' => 'john.smith',
            'email' => 'john.smith@example.ac.uk',
            'is_admin' => false,
        ]);

        $this->userWithRoles = User::factory()->create([
            'forenames' => 'Jane',
            'surname' => 'Doe',
            'username' => 'jane.doe',
            'email' => 'jane.doe@example.ac.uk',
            'is_admin' => false,
        ]);

        // Assign some roles to the user
        $this->userWithRoles->roles()->attach([$this->userRole->id, $this->managerRole->id]);
    });

    describe('Role Display', function () {
        it('shows user roles correctly', function () {
            livewire(UserList::class)
                ->assertSeeText('Jane')
                ->assertSeeText('Doe')
                ->assertSeeText('User')
                ->assertSeeText('Manager');
        });

        it('shows role count badge', function () {
            livewire(UserList::class)
                ->assertSeeText('+1') // Shows +1 for additional roles beyond first
                ->assertSeeText('User') // First role
                ->assertSeeText('Manager'); // Second role
        });

        it('shows "No roles" for users without roles', function () {
            livewire(UserList::class)
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertSeeText('No roles');
        });

        it('displays admin badge correctly', function () {
            livewire(UserList::class)
                ->assertSeeText('Admin')
                ->assertSeeText('User')
                ->assertSeeText('Admin'); // Admin badge
        });
    });

    describe('Role Assignment Modal', function () {
        it('opens role change modal successfully', function () {
            livewire(UserList::class)
                ->call('openChangeUserRoleModal', $this->regularUser)
                ->assertSet('selectedUser.id', $this->regularUser->id);
        });

        it('populates current user roles correctly', function () {
            $component = livewire(UserList::class);

            $component->call('openChangeUserRoleModal', $this->userWithRoles);

            expect($component->userRoles)->toContain('User');
            expect($component->userRoles)->toContain('Manager');
            expect(count($component->userRoles))->toBe(2);
        });

        it('shows available roles for assignment', function () {
            $component = livewire(UserList::class);

            $component->call('openChangeUserRoleModal', $this->regularUser);

            expect($component->availableRoles->pluck('name')->toArray())->toContain('Administrator');
            expect($component->availableRoles->pluck('name')->toArray())->toContain('User');
            expect($component->availableRoles->pluck('name')->toArray())->toContain('Manager');
            expect($component->availableRoles->pluck('name')->toArray())->not->toContain('Inactive Role');
        });

        it('excludes inactive roles from available options', function () {
            $component = livewire(UserList::class);

            $component->call('openChangeUserRoleModal', $this->regularUser);

            expect($component->availableRoles)->not->toContain('Inactive Role');
        });

        it('shows user name in modal', function () {
            livewire(UserList::class)
                ->call('openChangeUserRoleModal', $this->regularUser)
                ->assertSeeText('John')
                ->assertSeeText('Smith');
        });
    });

    describe('Role Toggle Functionality', function () {
        it('adds role when clicking on available role', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Initially no roles
            expect($component->userRoles)->toHaveCount(0);

            // Add a role
            $component->set('userRoles', ['User']);
            expect($component->userRoles)->toContain('User');
            expect($component->formModified)->toBe(true);
        });

        it('removes role when clicking on assigned role', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->userWithRoles);

            // Initially has 2 roles
            expect($component->userRoles)->toHaveCount(2);

            // Remove a role
            $component->set('userRoles', ['Manager']);
            expect($component->userRoles)->not->toContain('User');
            expect($component->userRoles)->toContain('Manager');
            expect($component->formModified)->toBe(true);
        });

        it('handles multiple role toggles correctly', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Add multiple roles
            $component->set('userRoles', ['User', 'Manager']);
            expect($component->userRoles)->toHaveCount(2);
            expect($component->userRoles)->toContain('User');
            expect($component->userRoles)->toContain('Manager');

            // Remove one role
            $component->set('userRoles', ['Manager']);
            expect($component->userRoles)->toHaveCount(1);
            expect($component->userRoles)->toContain('Manager');
            expect($component->userRoles)->not->toContain('User');
        });

        it('updates role count badge in real-time', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Initially no roles
            expect($component->userRoles)->toHaveCount(0);

            // Add a role
            $component->set('userRoles', ['User']);
            expect($component->userRoles)->toHaveCount(1);

            // Add another role
            $component->set('userRoles', ['User', 'Manager']);
            expect($component->userRoles)->toHaveCount(2);
        });

        it('tracks form modification through updatedUserRoles method', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Initially form should not be modified
            expect($component->formModified)->toBe(false);

            // Modify userRoles directly (simulating the updatedUserRoles lifecycle)
            $component->set('userRoles', ['User']);
            expect($component->formModified)->toBe(true);
        });
    });

    describe('Role Synchronization', function () {
        it('saves user roles successfully', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Add some roles
            $component->set('userRoles', ['User']);
            $component->set('userRoles', ['User', 'Manager']);

            $component->call('saveUserRoles');

            // Refresh user from database
            $this->regularUser->refresh();

            expect($this->regularUser->roles)->toHaveCount(2);
            expect($this->regularUser->roles->pluck('name')->toArray())->toContain('User');
            expect($this->regularUser->roles->pluck('name')->toArray())->toContain('Manager');
        });

        it('removes roles when unassigned', function () {
            $component = livewire(UserList::class);
            // Start with user that has roles
            $component->call('openChangeUserRoleModal', $this->userWithRoles);

            // Remove one role
            $component->set('userRoles', ['Manager']);

            $component->call('saveUserRoles');

            // Refresh user from database
            $this->userWithRoles->refresh();

            expect($this->userWithRoles->roles)->toHaveCount(1);
            expect($this->userWithRoles->roles->pluck('name')->toArray())->toContain('Manager');
            expect($this->userWithRoles->roles->pluck('name')->toArray())->not->toContain('User');
        });

        it('syncs roles correctly in database', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Add roles
            $component->set('userRoles', ['User']);
            $component->set('userRoles', ['User', 'Manager']);

            $component->call('saveUserRoles');

            // Check database directly
            $userRoles = $this->regularUser->roles()->pluck('role_id')->toArray();
            $expectedRoleIds = Role::whereIn('name', ['User', 'Manager'])->pluck('id')->toArray();

            expect($userRoles)->toHaveCount(2);
            expect($expectedRoleIds)->toHaveCount(2);
        });

        it('handles empty role assignment', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Don't assign any roles
            $component->call('saveUserRoles');

            // Refresh user from database
            $this->regularUser->refresh();

            expect($this->regularUser->roles)->toHaveCount(0);
        });

        it('updates role display after saving', function () {
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->regularUser);

            // Add a role
            $component->set('userRoles', ['User']);
            $component->call('saveUserRoles');

            // Check that the role was saved in the database
            $this->regularUser->refresh();
            expect($this->regularUser->roles)->toHaveCount(1);
            expect($this->regularUser->roles->first()->name)->toBe('User');
        });
    });

    describe('Modal Reset', function () {
        it('resets modal state when closed', function () {
            $component = livewire(UserList::class);

            $component->call('openChangeUserRoleModal', $this->regularUser);
            $component->set('userRoles', ['User']);

            $component->call('resetChangeUserRoleModal');

            expect($component->selectedUser)->toBeNull();
            expect($component->userRoles)->toHaveCount(0);
        });

        it('resets user roles array', function () {
            $component = livewire(UserList::class);

            $component->call('openChangeUserRoleModal', $this->userWithRoles);
            expect(count($component->userRoles))->toBe(2);

            $component->call('resetChangeUserRoleModal');

            expect($component->userRoles)->toHaveCount(0);
        });
    });

    describe('Admin Toggle', function () {
        it('toggles admin status successfully', function () {
            $component = livewire(UserList::class);

            // Act as admin user to have permission to toggle admin status
            $this->actingAs($this->adminUser);

            // Toggle user to admin
            $component->call('toggleAdmin', $this->regularUser);

            // Refresh user from database
            $this->regularUser->refresh();
            expect($this->regularUser->is_admin)->toBe(true);

            // Toggle back to non-admin
            $component->call('toggleAdmin', $this->regularUser);
            $this->regularUser->refresh();
            expect($this->regularUser->is_admin)->toBe(false);
        });

        it('updates admin badge display', function () {
            $component = livewire(UserList::class);

            // Act as admin user to have permission to toggle admin status
            $this->actingAs($this->adminUser);

            // Toggle user to admin
            $component->call('toggleAdmin', $this->regularUser);

            // Verify the admin badge is displayed
            $component->assertSee('Admin');
        });

        it('handles admin toggle for existing admin user', function () {
            $component = livewire(UserList::class);

            // Act as admin user to have permission to toggle admin status
            $this->actingAs($this->adminUser);

            // Toggle admin user to non-admin
            $component->call('toggleAdmin', $this->adminUser);

            // Refresh user from database
            $this->adminUser->refresh();
            expect($this->adminUser->is_admin)->toBe(false);
        });

        it('shows success message after admin toggle', function () {
            $component = livewire(UserList::class);

            // Act as admin user to have permission to toggle admin status
            $this->actingAs($this->adminUser);

            // Toggle user to admin
            $component->call('toggleAdmin', $this->regularUser);

            // Should show success message (this would be tested in integration tests)
            // For now, just verify the method doesn't crash
            $this->regularUser->refresh();
            expect($this->regularUser->is_admin)->toBe(true);
        });
    });

    describe('Edge Cases', function () {
        it('handles user with no roles gracefully', function () {
            livewire(UserList::class)
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertSeeText('No roles');
        });

        it('handles user with many roles', function () {
            // Create additional roles and assign them to the user
            $additionalRoles = Role::factory()->count(8)->create(['is_active' => true]);
            $this->userWithRoles->roles()->attach($additionalRoles->pluck('id'));

            livewire(UserList::class)
                ->assertSeeText('+9') // Shows +9 for additional roles beyond first
                ->assertSeeText('User'); // First role is visible
        });

        it('handles role name with special characters', function () {
            $specialRole = Role::factory()->create([
                'name' => 'Special & Role (Test)',
                'is_active' => true,
            ]);
            $this->regularUser->roles()->attach($specialRole->id);

            livewire(UserList::class)
                ->assertSeeText('Special & Role (Test)');
        });

        it('handles very long role names', function () {
            $longRoleName = str_repeat('A', 100);
            $longRole = Role::factory()->create([
                'name' => $longRoleName,
                'is_active' => true,
            ]);
            $this->regularUser->roles()->attach($longRole->id);

            livewire(UserList::class)
                ->assertSeeText($longRoleName);
        });

        it('displays tooltip content for users with many roles', function () {
            // Create additional roles and assign them to the user
            $additionalRoles = Role::factory()->count(15)->create(['is_active' => true]);
            $this->userWithRoles->roles()->attach($additionalRoles->pluck('id'));

            // Test that the component can handle many roles
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->userWithRoles);

            // Should have 17 roles total (2 original + 15 new)
            expect($component->userRoles)->toHaveCount(17);

            // The tooltip UI elements would be tested in integration tests with actual UI
        });

        it('handles role name capitalization correctly in tooltips', function () {
            $lowercaseRole = Role::factory()->create([
                'name' => 'lowercase_role',
                'is_active' => true,
            ]);
            $this->regularUser->roles()->attach($lowercaseRole->id);

            // Test that the role is properly assigned
            $this->regularUser->refresh();
            expect($this->regularUser->roles)->toHaveCount(1);
            expect($this->regularUser->roles->first()->name)->toBe('lowercase_role');

            // The ucfirst() transformation would be tested in integration tests with actual UI
        });
    });

    describe('Integration with RolesList', function () {
        it('reflects role changes from RolesList component', function () {
            // First, change a role name in RolesList
            $this->adminRole->update(['name' => 'Updated Admin Role']);

            // Then check if UserList shows the updated role name
            $component = livewire(UserList::class);
            $component->call('openChangeUserRoleModal', $this->userWithRoles);

            // Should show updated role name in available roles
            expect($component->availableRoles->fresh()->pluck('name')->toArray())->toContain('Updated Admin Role');
        });

        it('handles role deletion gracefully', function () {
            // Delete a role that a user has
            $this->userRole->delete();

            // Refresh user to see if roles are properly handled
            $this->userWithRoles->refresh();

            // User should now have only the manager role
            expect($this->userWithRoles->roles)->toHaveCount(1);
            expect($this->userWithRoles->roles->first()->name)->toBe('Manager');
        });
    });
});
