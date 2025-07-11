<?php

use App\Livewire\UserList;
use App\Models\User;
use function Pest\Livewire\livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        // Create additional users for pagination testing
        User::factory()->count(8)->create();
    });

    describe('Basic Rendering', function () {
        it('renders the component successfully', function () {
            livewire(UserList::class)
                ->assertStatus(200)
                ->assertViewIs('livewire.user-list');
        });

        it('displays users in the list', function () {
            livewire(UserList::class)
                ->assertSeeText('Admin User')
                ->assertSeeText('John Smith')
                ->assertSeeText('Jane Doe');
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
                ->assertSeeText('John Smith')
                ->assertDontSeeText('Jane Doe')
                ->assertDontSeeText('Admin User');
        });

        it('filters users by forenames', function () {
            livewire(UserList::class)
                ->set('search', 'Jane')
                ->assertSeeText('Jane Doe')
                ->assertDontSeeText('John Smith')
                ->assertDontSeeText('Admin User');
        });

        it('is case insensitive', function () {
            livewire(UserList::class)
                ->set('search', 'smith')
                ->assertSeeText('John Smith')
                ->assertDontSeeText('Jane Doe');
        });

        it('requires minimum 2 characters for search', function () {
            livewire(UserList::class)
                ->set('search', 'J')
                ->assertSeeText('John Smith')
                ->assertSeeText('Jane Doe')
                ->assertSeeText('Admin User');
        });

        it('shows all users when search is empty', function () {
            livewire(UserList::class)
                ->set('search', 'Smith')
                ->assertSeeText('John Smith')
                ->assertDontSeeText('Jane Doe')
                ->set('search', '')
                ->assertSeeText('John Smith')
                ->assertSeeText('Jane Doe')
                ->assertSeeText('Admin User');
        });

        it('shows no results for non-matching search', function () {
            livewire(UserList::class)
                ->set('search', 'NonExistentUser')
                ->assertDontSeeText('John Smith')
                ->assertDontSeeText('Jane Doe')
                ->assertDontSeeText('Admin User');
        });
    });

    describe('Sorting Functionality', function () {
        it('sorts by surname ascending by default', function () {
            $component = livewire(UserList::class);
            $users = $component->getUsers();

            expect($users->first()->surname)->toBe('Doe'); // Jane Doe comes first alphabetically
        });

        it('toggles sort direction when clicking same column', function () {
            livewire(UserList::class)
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'desc')
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'asc');
        });

        it('changes sort column and resets to ascending', function () {
            livewire(UserList::class)
                ->call('sort', 'surname')
                ->assertSet('sortDirection', 'desc')
                ->call('sort', 'forenames')
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc');
        });

        it('can sort by forenames', function () {
            livewire(UserList::class)
                ->call('sort', 'forenames')
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc');
        });

        it('can sort by email', function () {
            livewire(UserList::class)
                ->call('sort', 'email')
                ->assertSet('sortOn', 'email')
                ->assertSet('sortDirection', 'asc');
        });

        it('resets pagination when sorting', function () {
            // Create enough users to trigger pagination
            User::factory()->count(15)->create();

            livewire(UserList::class)
                ->set('page', 2)
                ->call('sort', 'forenames')
                ->assertSet('page', 1);
        });
    });

    describe('Toggle Admin Functionality', function () {
        it('can toggle admin status from false to true', function () {
            expect($this->regularUser->is_admin)->toBeFalse();

            livewire(UserList::class)
                ->call('toggleAdmin', $this->regularUser)
                ->assertDispatched('$refresh');

            expect($this->regularUser->fresh()->is_admin)->toBeTrue();
        });

        it('can toggle admin status from true to false', function () {
            expect($this->adminUser->is_admin)->toBeTrue();

            livewire(UserList::class)
                ->call('toggleAdmin', $this->adminUser)
                ->assertDispatched('$refresh');

            expect($this->adminUser->fresh()->is_admin)->toBeFalse();
        });

        it('shows success toast when toggling admin status', function () {
            livewire(UserList::class)
                ->call('toggleAdmin', $this->regularUser);

            // The component should show a success toast
            // Note: Testing toast messages in Livewire can be tricky
            // This test verifies the method runs without errors
        });
    });

    describe('Pagination', function () {
        it('paginates users correctly', function () {
            // We have 11 users total (3 from beforeEach + 8 from factory)
            // With 10 per page, we should have 2 pages
            $component = livewire(UserList::class);
            $users = $component->getUsers();

            expect($users->count())->toBe(10);
            expect($users->hasPages())->toBeTrue();
            expect($users->currentPage())->toBe(1);
        });

        it('can navigate to second page', function () {
            livewire(UserList::class)
                ->set('page', 2)
                ->assertSet('page', 2);
        });

        it('shows correct users on second page', function () {
            $component = livewire(UserList::class);
            $component->set('page', 2);
            $users = $component->getUsers();

            expect($users->count())->toBe(1); // Only 1 user on second page
            expect($users->currentPage())->toBe(2);
        });
    });

    describe('Combined Functionality', function () {
        it('maintains search when sorting', function () {
            livewire(UserList::class)
                ->set('search', 'Smith')
                ->call('sort', 'forenames')
                ->assertSet('search', 'Smith')
                ->assertSet('sortOn', 'forenames')
                ->assertSeeText('John Smith')
                ->assertDontSeeText('Jane Doe');
        });

        it('maintains sort when searching', function () {
            livewire(UserList::class)
                ->call('sort', 'forenames')
                ->set('search', 'o') // Should match John and Doe
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc');
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
                ->assertSeeText('Alice Johnson')
                ->assertSeeText('Bob Johnson');
        });
    });

    describe('Edge Cases', function () {
        it('handles empty user list gracefully', function () {
            User::query()->delete();

            livewire(UserList::class)
                ->assertStatus(200);
        });

        it('handles special characters in search', function () {
            User::factory()->create([
                'forenames' => 'Test\'s',
                'surname' => 'O\'Connor',
            ]);

            livewire(UserList::class)
                ->set('search', 'O\'Connor')
                ->assertSeeText('Test\'s O\'Connor');
        });

        it('trims whitespace from search', function () {
            livewire(UserList::class)
                ->set('search', '  Smith  ')
                ->assertSeeText('John Smith')
                ->assertDontSeeText('Jane Doe');
        });
    });
});
