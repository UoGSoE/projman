<?php

use App\Livewire\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('UserList Component', function () {
    beforeEach(function () {
        // Create test users with varying data for comprehensive testing
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

        $this->anotherUser = User::factory()->create([
            'forenames' => 'Jane',
            'surname' => 'Doe',
            'username' => 'jane.doe',
            'email' => 'jane.doe@example.ac.uk',
            'is_admin' => false,
        ]);
    });

    describe('Permissions', function () {
        it('can only be accessed by admins', function () {
            $this->actingAs($this->regularUser);
            $this->get(route('users.list'))->assertForbidden();
        });

        it('can be accessed by admins', function () {
            $this->actingAs($this->adminUser);
            $this->get(route('users.list'))->assertOk();
        });

        it('forbids a non-admin from saving a user via Livewire', function () {
            $this->actingAs($this->regularUser);

            livewire(UserList::class)
                ->set('userAttributes', [
                    'id' => null,
                    'username' => 'sneaky.user',
                    'email' => 'sneaky@example.ac.uk',
                    'surname' => 'Sneaky',
                    'forenames' => 'User',
                    'is_admin' => true,
                    'is_itstaff' => true,
                ])
                ->call('saveUser')
                ->assertForbidden();

            expect(User::where('username', 'sneaky.user')->exists())->toBeFalse();
        });

        it('forbids a non-admin from saving user roles via Livewire', function () {
            $role = Role::factory()->create(['name' => 'Sensitive Role', 'is_active' => true]);

            $this->actingAs($this->regularUser);

            livewire(UserList::class)
                ->call('openChangeUserRoleModal', $this->anotherUser)
                ->set('userRoleIds', [$role->id])
                ->call('saveUserRoles')
                ->assertForbidden();

            expect($this->anotherUser->fresh()->roles()->count())->toBe(0);
        });
    });

    describe('Basic Rendering', function () {
        it('renders the component successfully', function () {
            livewire(UserList::class)
                ->assertStatus(200)
                ->assertViewIs('livewire.user-list');
        });

        it('displays users in the list', function () {
            livewire(UserList::class)
                ->assertSeeText('Admin')
                ->assertSeeText('User')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertSeeText('Jane')
                ->assertSeeText('Doe');
        });

        it('has default sort settings', function () {
            $component = livewire(UserList::class);

            expect($component->sortOn)->toBe('surname');
            expect($component->sortDirection)->toBe('asc');
        });

        it('has empty search by default', function () {
            $component = livewire(UserList::class);

            expect($component->search)->toBe('');
        });
    });

    describe('Search Functionality', function () {
        it('filters users by surname', function () {
            livewire(UserList::class)
                ->set('search', 'Smith')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe')
                ->assertDontSeeText('admin.user@example.ac.uk');
        });

        it('filters users by forenames', function () {
            livewire(UserList::class)
                ->set('search', 'Jane')
                ->assertSeeText('Jane')
                ->assertSeeText('Doe')
                ->assertDontSeeText('John')
                ->assertDontSeeText('Smith')
                ->assertDontSeeText('admin.user@example.ac.uk');
        });

        it('is case insensitive', function () {
            livewire(UserList::class)
                ->set('search', 'smith')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe');
        });

        it('requires minimum 2 characters for search', function () {
            livewire(UserList::class)
                ->set('search', 'J')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertSeeText('Jane')
                ->assertSeeText('Doe')
                ->assertSeeText('Admin')
                ->assertSeeText('User');
        });

        it('shows all users when search is empty', function () {
            livewire(UserList::class)
                ->set('search', 'Smith')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe')
                ->set('search', '')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertSeeText('Jane')
                ->assertSeeText('Doe')
                ->assertSeeText('Admin')
                ->assertSeeText('User');
        });

        it('shows no results for non-matching search', function () {
            livewire(UserList::class)
                ->set('search', 'NonExistentUser')
                ->assertDontSeeText('John')
                ->assertDontSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe')
                ->assertDontSeeText('admin.user@example.ac.uk');
        });
    });

    describe('Sorting Functionality', function () {
        it('sorts by surname ascending by default', function () {
            livewire(UserList::class)
                ->assertSeeInOrder(['Doe', 'Smith', 'User']); // Jane Doe, John Smith, Admin User
        });

        it('toggles sort direction when clicking same column', function () {
            livewire(UserList::class)
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'desc')
                ->assertSeeInOrder(['User', 'Smith', 'Doe']) // Reverse alphabetical order
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Doe', 'Smith', 'User']); // Back to ascending
        });

        it('changes sort column and resets to ascending', function () {
            livewire(UserList::class)
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'desc')
                ->call('sort', 'forenames')
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Admin', 'Jane', 'John']); // Sorted by forenames ascending
        });

        it('can sort by forenames', function () {
            livewire(UserList::class)
                ->call('sort', 'forenames')
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Admin', 'Jane', 'John']); // Admin, Jane, John alphabetically
        });

        it('can sort by email', function () {
            livewire(UserList::class)
                ->call('sort', 'email')
                ->assertSet('sortOn', 'email')
                ->assertSet('sortDirection', 'asc')
                // Check that emails appear in alphabetical order
                ->assertSeeInOrder(['admin.user@example.ac.uk', 'jane.doe@example.ac.uk', 'john.smith@example.ac.uk']);
        });
    });

    describe('Pagination', function () {
        it('paginates users correctly', function () {
            livewire(UserList::class)
                ->assertSeeInOrder(['Doe', 'Smith', 'User']); // Jane Doe, John Smith, Admin User
        });
    });

    describe('Combined Functionality', function () {
        it('maintains search when sorting', function () {
            livewire(UserList::class)
                ->set('search', 'Smith')
                ->call('sort', 'forenames')
                ->assertSet('search', 'Smith')
                ->assertSet('sortOn', 'forenames')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe');
        });

        it('maintains the sort order while a search filter is applied', function () {
            // Two users sharing a surname the search can match, plus the baseline
            // John Smith / Jane Doe / Admin User who should be filtered out.
            User::factory()->create(['forenames' => 'Zoe', 'surname' => 'Filterton']);
            User::factory()->create(['forenames' => 'Amy', 'surname' => 'Filterton']);

            livewire(UserList::class)
                ->call('sort', 'forenames')
                ->set('search', 'Filterton')
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Amy', 'Zoe']) // sorted by forenames within the filtered set
                ->assertDontSeeText('John') // non-matching users are excluded by the search
                ->assertDontSeeText('Jane');
        });

        it('can search and sort together', function () {
            // Create users with similar names for better testing
            User::factory()->create([
                'forenames' => 'Alice',
                'surname' => 'Johnson',
            ]);

            User::factory()->create([
                'forenames' => 'Bob',
                'surname' => 'Johnson',
            ]);

            livewire(UserList::class)
                ->set('search', 'Johnson')
                ->call('sort', 'forenames')
                ->assertSet('search', 'Johnson')
                ->assertSet('sortOn', 'forenames')
                ->assertSeeInOrder(['Alice', 'Bob']); // Alice comes before Bob alphabetically
        });
    });

    describe('Edge Cases', function () {
        it('handles empty user list gracefully', function () {
            User::query()->delete();

            livewire(UserList::class)
                ->assertStatus(200);
        });

        it('returns the matching user when the search contains special characters', function () {
            $userWithSpecialChars = User::factory()->create([
                'surname' => "Test's",
                'forenames' => 'User & Co.',
            ]);

            // Asserting against the component's data sidesteps HTML-escaping of the
            // apostrophe/ampersand, and proves the search actually returns the user.
            $results = livewire(UserList::class)
                ->set('search', "Test's")
                ->assertOk()
                ->viewData('users');

            expect($results->getCollection()->pluck('id'))
                ->toContain($userWithSpecialChars->id)
                ->not->toContain($this->regularUser->id);
        });
    });
});

describe('Create User', function () {
    beforeEach(function () {
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->adminUser);
    });

    it('validates required fields and does not create a user', function () {
        livewire(UserList::class)
            ->set('userAttributes.username', '')
            ->set('userAttributes.email', '')
            ->set('userAttributes.surname', '')
            ->set('userAttributes.forenames', '')
            ->call('saveUser')
            ->assertHasErrors(['userAttributes.username', 'userAttributes.email', 'userAttributes.surname', 'userAttributes.forenames']);

        // Only the admin from beforeEach should exist
        expect(User::count())->toBe(1);
    });

    it('prevents duplicate usernames and emails', function () {
        User::factory()->create(['username' => 'taken', 'email' => 'taken@example.ac.uk']);
        $countBefore = User::count();

        livewire(UserList::class)
            ->set('userAttributes.username', 'taken')
            ->set('userAttributes.email', 'taken@example.ac.uk')
            ->set('userAttributes.surname', 'Test')
            ->set('userAttributes.forenames', 'User')
            ->call('saveUser')
            ->assertHasErrors(['userAttributes.username', 'userAttributes.email']);

        expect(User::count())->toBe($countBefore);
    });

    it('clears form fields when opening the create modal', function () {
        livewire(UserList::class)
            ->set('userAttributes.username', 'leftover')
            ->set('userAttributes.email', 'leftover@example.ac.uk')
            ->call('openUserModal')
            ->assertSet('userAttributes.username', '')
            ->assertSet('userAttributes.email', '')
            ->assertSet('userAttributes.surname', '')
            ->assertSet('userAttributes.forenames', '')
            ->assertSet('userAttributes.is_admin', false)
            ->assertSet('userAttributes.is_itstaff', false)
            ->assertSet('userAttributes.id', null);
    });

    it('creates a user with valid data and lowercases the email', function () {
        livewire(UserList::class)
            ->set('userAttributes.username', 'newuser')
            ->set('userAttributes.email', 'New.User@Example.AC.UK')
            ->set('userAttributes.surname', 'Bloggs')
            ->set('userAttributes.forenames', 'Joe')
            ->set('userAttributes.is_admin', false)
            ->call('saveUser')
            ->assertHasNoErrors();

        $user = User::where('username', 'newuser')->first();
        expect($user)->not->toBeNull();
        expect($user->email)->toBe('new.user@example.ac.uk');
        expect($user->surname)->toBe('Bloggs');
        expect($user->forenames)->toBe('Joe');
        expect($user->is_staff)->toBeTrue();
        expect($user->is_admin)->toBeFalse();
    });
});

describe('User Role Management', function () {
    beforeEach(function () {
        // Create roles for assignment
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

        // Create users
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($this->adminUser);
    });

    it('opens change user role modal and loads roles correctly', function () {
        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $this->regularUser->id)
            ->assertSet('selectedUser.id', $this->regularUser->id)
            ->assertSet(
                'availableRoles',
                fn ($roles) => $roles instanceof Collection &&
                collect([$this->adminRole->name, $this->userRole->name, $this->managerRole->name])->diff($roles->pluck('name'))->isEmpty()
            )
            ->assertSet('userRoleIds', []);
    });

    it('preloads the users existing roles when opening the role modal', function () {
        $this->regularUser->roles()->sync([$this->adminRole->id, $this->managerRole->id]);

        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $this->regularUser)
            ->assertSet('selectedUser.id', $this->regularUser->id)
            ->assertSet('userRoleIds', fn ($ids) => count($ids) === 2
                && in_array($this->adminRole->id, $ids)
                && in_array($this->managerRole->id, $ids));
    });

    it('saves selected roles to user', function () {
        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $this->regularUser)
            ->set('userRoleIds', [$this->adminRole->id, $this->managerRole->id])
            ->call('saveUserRoles');

        $this->assertTrue($this->regularUser->roles()->where('name', 'Administrator')->exists());
        $this->assertTrue($this->regularUser->roles()->where('name', 'Manager')->exists());
    });

    it('coerces a scalar role id into an array when the client sends a string', function () {
        livewire(UserList::class)
            ->call('openChangeUserRoleModal', $this->regularUser)
            ->set('userRoleIds', (string) $this->adminRole->id)
            ->assertSet('userRoleIds', [(string) $this->adminRole->id])
            ->call('saveUserRoles')
            ->assertHasNoErrors();

        expect($this->regularUser->roles()->where('name', 'Administrator')->exists())->toBeTrue();
    });
});

describe('IT Staff Toggling', function () {
    beforeEach(function () {
        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    });

    it('toggles a user\'s IT staff status both ways', function () {
        $target = User::factory()->requester()->create();

        livewire(UserList::class)
            ->call('toggleItStaff', $target);
        expect($target->fresh()->is_itstaff)->toBeTrue();

        livewire(UserList::class)
            ->call('toggleItStaff', $target->fresh());
        expect($target->fresh()->is_itstaff)->toBeFalse();
    });

    it('forbids a non-admin from toggling IT staff status and leaves it unchanged', function () {
        $nonAdmin = User::factory()->staff()->create();
        $target = User::factory()->requester()->create();

        $this->actingAs($nonAdmin);

        livewire(UserList::class)
            ->call('toggleItStaff', $target)
            ->assertForbidden();

        expect($target->fresh()->is_itstaff)->toBeFalse();
    });

    it('persists the IT Staff checkbox when creating a user', function () {
        livewire(UserList::class)
            ->set('userAttributes.username', 'newitstaff')
            ->set('userAttributes.email', 'new.it@example.ac.uk')
            ->set('userAttributes.surname', 'Bloggs')
            ->set('userAttributes.forenames', 'Joe')
            ->set('userAttributes.is_itstaff', true)
            ->call('saveUser')
            ->assertHasNoErrors();

        expect(User::where('username', 'newitstaff')->first()->is_itstaff)->toBeTrue();
    });

    it('shows an IT Staff badge for IT-staff users who are not admins', function () {
        User::factory()->staff()->create(['surname' => 'ItPersonBadgeTest']);

        livewire(UserList::class)
            ->assertSeeTextInOrder(['ItPersonBadgeTest', 'IT Staff']);
    });
});

describe('Editing Users', function () {
    beforeEach(function () {
        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    });

    it('populates the modal form fields when opening the edit modal', function () {
        $target = User::factory()->create([
            'username' => 'original.name',
            'email' => 'original@example.ac.uk',
            'surname' => 'Original',
            'forenames' => 'Name',
        ]);

        livewire(UserList::class)
            ->call('openUserModal', $target)
            ->assertSet('userAttributes.username', 'original.name')
            ->assertSet('userAttributes.email', 'original@example.ac.uk')
            ->assertSet('userAttributes.surname', 'Original')
            ->assertSet('userAttributes.forenames', 'Name');
    });

    it('updates the existing user when saving from the edit modal', function () {
        $target = User::factory()->create(['surname' => 'Original']);
        $countBefore = User::count();

        livewire(UserList::class)
            ->call('openUserModal', $target)
            ->set('userAttributes.surname', 'Updated')
            ->call('saveUser')
            ->assertHasNoErrors();

        expect($target->fresh()->surname)->toBe('Updated');
        expect(User::count())->toBe($countBefore);
    });

    it('allows keeping the same username and email when editing', function () {
        $target = User::factory()->create([
            'username' => 'keepme',
            'email' => 'keep.me@example.ac.uk',
        ]);

        livewire(UserList::class)
            ->call('openUserModal', $target)
            ->set('userAttributes.surname', 'NewName')
            ->call('saveUser')
            ->assertHasNoErrors();

        expect($target->fresh()->username)->toBe('keepme');
    });

    it('does not change an admins own is_admin when they save their own edit', function () {
        livewire(UserList::class)
            ->call('openUserModal', $this->adminUser)
            ->set('userAttributes.is_admin', false)
            ->call('saveUser')
            ->assertHasNoErrors();

        expect($this->adminUser->fresh()->is_admin)->toBeTrue();
    });
});
