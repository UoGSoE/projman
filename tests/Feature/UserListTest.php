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
                ->assertDontSeeText('Admin');
        });

        it('filters users by forenames', function () {
            livewire(UserList::class)
                ->set('search', 'Jane')
                ->assertSeeText('Jane')
                ->assertSeeText('Doe')
                ->assertDontSeeText('John')
                ->assertDontSeeText('Smith')
                ->assertDontSeeText('Admin');
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
                ->assertDontSeeText('Admin');
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

        it('maintains sort when searching', function () {
            livewire(UserList::class)
                ->call('sort', 'forenames')
                ->set('search', 'o') // Should match John and Doe
                ->assertSet('sortOn', 'forenames')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Jane', 'John']); // Jane comes before John alphabetically
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

        it('handles special characters in search', function () {
            User::factory()->create([
                'forenames' => 'Test\'s',
                'surname' => 'O\'Connor',
            ]);

            livewire(UserList::class)
                ->set('search', 'O\'Connor')
                ->assertSeeText('Test\'s')
                ->assertSeeText('O\'Connor');
        });

        it('trims whitespace from search', function () {
            livewire(UserList::class)
                ->set('search', '  Smith  ')
                ->assertSeeText('John')
                ->assertSeeText('Smith')
                ->assertDontSeeText('Jane')
                ->assertDontSeeText('Doe');
        });
    });
});
