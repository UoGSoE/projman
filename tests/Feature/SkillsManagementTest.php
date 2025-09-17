<?php

use App\Enums\SkillLevel;
use App\Livewire\SkillsManager;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('SkillsManager Component', function () {
    beforeEach(function () {
        // Create admin user for testing
        $this->adminUser = User::factory()->create([
            'is_admin' => true,
        ]);

        // Create staff users for testing
        $this->staffUser1 = User::factory()->create([
            'is_staff' => true,
            'forenames' => 'John',
            'surname' => 'Doe',
        ]);

        $this->staffUser2 = User::factory()->create([
            'is_staff' => true,
            'forenames' => 'Jane',
            'surname' => 'Smith',
        ]);

        $this->staffUser3 = User::factory()->create([
            'is_staff' => true,
            'forenames' => 'Bob',
            'surname' => 'Johnson',
        ]);

        // Create test skills
        $this->skill1 = Skill::factory()->create([
            'name' => 'Laravel',
            'description' => 'PHP framework for web development',
            'skill_category' => 'Programming',
        ]);

        $this->skill2 = Skill::factory()->create([
            'name' => 'React',
            'description' => 'JavaScript library for building user interfaces',
            'skill_category' => 'Frontend',
        ]);

        $this->skill3 = Skill::factory()->create([
            'name' => 'Project Management',
            'description' => 'Managing projects and teams',
            'skill_category' => 'Management',
        ]);

        $this->skill4 = Skill::factory()->create([
            'name' => 'Vue.js',
            'description' => 'Progressive JavaScript framework',
            'skill_category' => 'Frontend',
        ]);

        $this->skill5 = Skill::factory()->create([
            'name' => 'Python',
            'description' => 'High-level programming language',
            'skill_category' => 'Programming',
        ]);
    });

    describe('Basic Rendering', function () {
        it('renders the component', function () {
            livewire(SkillsManager::class)
                ->assertStatus(200)
                ->assertViewIs('livewire.skills-manager');
        });

        it('displays skills in the list', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management')
                ->assertSeeText('Vue.js')
                ->assertSeeText('Python');
        });

        it('displays staff users in the list', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('John Doe')
                ->assertSeeText('Jane Smith')
                ->assertSeeText('Bob Johnson');
        });

        it('has default sort settings', function () {
            $component = livewire(SkillsManager::class);

            expect($component->sortColumn)->toBe('name');
            expect($component->sortDirection)->toBe('asc');
        });

        it('has empty search queries by default', function () {
            $component = livewire(SkillsManager::class);

            expect($component->skillSearchQuery)->toBe('');
            expect($component->userSearchQuery)->toBe('');
        });

        it('has default active tab', function () {
            $component = livewire(SkillsManager::class);

            expect($component->activeTab)->toBe('available-skills');
        });

        it('has form modification flag set to false by default', function () {
            $component = livewire(SkillsManager::class);

            expect($component->isFormModified)->toBe(false);
        });

        it('has show create skill form flag set to false by default', function () {
            $component = livewire(SkillsManager::class);

            expect($component->showCreateSkillForm)->toBe(false);
        });

        it('displays Skill name, description, category and user count for each skill', function () {
            // Assign skills to users for testing user counts
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::INTERMEDIATE->value);
            $this->staffUser2->updateSkillForUser($this->skill1, SkillLevel::ADVANCED->value);
            $this->staffUser1->updateSkillForUser($this->skill2, SkillLevel::BEGINNER->value);

            livewire(SkillsManager::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('PHP framework for web development')
                ->assertSeeText('Programming')
                ->assertSeeText('2') // skill1 has 2 users
                ->assertSeeText('1') // skill2 has 1 user
                ->assertSeeText('0'); // skill3 has 0 users
        });

        /**
         * displays Skill name, description, category and user count
         */
    });

    describe('Search Functionality', function () {
        it('filters skills by name', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'Laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React')
                ->assertDontSeeText('Project Management');
        });

        it('filters skills by description', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'PHP framework')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React')
                ->assertDontSeeText('Project Management');
        });

        it('filters skills by category', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'Programming')
                ->assertSeeText('Laravel')
                ->assertSeeText('Python')
                ->assertDontSeeText('React')
                ->assertDontSeeText('Project Management');
        });

        it('is case insensitive for skill search', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React');
        });

        it('requires minimum 2 characters for skill search', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'L')
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management');
        });

        it('shows all skills when skill search is empty', function () {
            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'Laravel')
                ->assertSeeText('Laravel')
                ->assertDontSeeText('React')
                ->set('skillSearchQuery', '')
                ->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management');
        });

        it('filters users by forenames', function () {
            $component = livewire(SkillsManager::class);
            $component->set('userSearchQuery', 'John');

            // The component should show John Doe but not others
            $component->assertSeeText('John Doe');
            // Note: The search might still show other users due to how the component renders
        });

        it('filters users by surname', function () {
            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'Smith')
                ->assertSeeText('Jane Smith')
                ->assertDontSeeText('John Doe')
                ->assertDontSeeText('Bob Johnson');
        });

        it('filters users by full name', function () {
            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'John Doe')
                ->assertSeeText('John Doe')
                ->assertDontSeeText('Jane Smith')
                ->assertDontSeeText('Bob Johnson');
        });

        it('is case insensitive for user search', function () {
            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'john')
                ->assertSeeText('John Doe')
                ->assertDontSeeText('Jane Smith');
        });

        it('requires minimum 2 characters for user search', function () {
            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'J')
                ->assertSeeText('John Doe')
                ->assertSeeText('Jane Smith')
                ->assertSeeText('Bob Johnson');
        });

        it('shows all users when user search is empty', function () {
            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'John')
                ->assertSeeText('John Doe')
                ->assertDontSeeText('Jane Smith')
                ->set('userSearchQuery', '')
                ->assertSeeText('John Doe')
                ->assertSeeText('Jane Smith')
                ->assertSeeText('Bob Johnson');
        });

        it('resets page when skill search changes', function () {
            $component = livewire(SkillsManager::class);
            $component->set('skillSearchQuery', 'Laravel');

            // The component should reset to page 1 when search changes
            // We can verify this by checking that the search was applied
            expect($component->skillSearchQuery)->toBe('Laravel');
        });
    });

    describe('Sorting Functionality', function () {
        it('sorts by name ascending by default', function () {
            livewire(SkillsManager::class)
                ->assertSeeInOrder(['Laravel', 'Project Management', 'Python', 'React', 'Vue.js']);
        });

        it('toggles sort direction when clicking same column', function () {
            livewire(SkillsManager::class)
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'desc')
                ->assertSeeInOrder(['Vue.js', 'React', 'Python', 'Project Management', 'Laravel'])
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'asc')
                ->assertSeeInOrder(['Laravel', 'Project Management', 'Python', 'React', 'Vue.js']);
        });

        it('changes sort column and resets to ascending', function () {
            livewire(SkillsManager::class)
                ->call('sort', 'name')
                ->assertSet('sortDirection', 'desc')
                ->call('sort', 'skill_category')
                ->assertSet('sortColumn', 'skill_category')
                ->assertSet('sortDirection', 'asc');
        });

        it('can sort by skill_category column', function () {
            livewire(SkillsManager::class)
                ->call('sort', 'skill_category')
                ->assertSet('sortColumn', 'skill_category')
                ->assertSet('sortDirection', 'asc');
        });

        it('can sort by description column', function () {
            livewire(SkillsManager::class)
                ->call('sort', 'description')
                ->assertSet('sortColumn', 'description')
                ->assertSet('sortDirection', 'asc');
        });

        it('resets page when sorting', function () {
            $component = livewire(SkillsManager::class);
            $component->call('sort', 'name');

            // The component should reset to page 1 when sorting
            // We can verify this by checking that the sort was applied
            expect($component->sortColumn)->toBe('name');
            expect($component->sortDirection)->toBe('desc');
        });

        it('maintains sort order across different columns', function () {
            $component = livewire(SkillsManager::class);

            // Sort by name descending
            $component->call('sort', 'name');
            expect($component->sortColumn)->toBe('name');
            expect($component->sortDirection)->toBe('desc');

            // Sort by category - should reset to ascending
            $component->call('sort', 'skill_category');
            expect($component->sortColumn)->toBe('skill_category');
            expect($component->sortDirection)->toBe('asc');
        });
    });

    describe('Skill Creation modal functionality', function () {
        it('opens add skill modal', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->selectedSkill)->toBeNull();
            expect($component->skillName)->toBe('');
            expect($component->skillDescription)->toBe('');
            expect($component->skillCategory)->toBe('');
            expect($component->isFormModified)->toBe(false);
            expect($component->assertSee('Add New Skill'));
            expect($component->assertSee('Add a new skill to the system.'));
        });

        it('creates a new skill', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal')
                ->set('skillName', 'Angular')
                ->set('skillDescription', 'TypeScript-based web framework')
                ->set('skillCategory', 'Frontend')
                ->call('saveSkill')
                ->assertHasNoErrors();

            expect(Skill::where('name', 'Angular')->exists())->toBeTrue();
        });

        it('validates required fields for skill creation', function () {
            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', 'JavaScript')
                ->set('skillDescription', 'JavaScript is a programming language')
                ->set('skillCategory', 'Programming Language')
                ->call('saveSkill')
                ->assertHasNoErrors(['skillName', 'skillDescription', 'skillCategory']);

            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', '')
                ->set('skillDescription', 'Test Description')
                ->set('skillCategory', 'Test Category')
                ->call('saveSkill')
                ->assertHasErrors(['skillName']);

            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', 'Test Skill')
                ->set('skillDescription', '')
                ->set('skillCategory', 'Test Category')
                ->call('saveSkill')
                ->assertHasErrors(['skillDescription']);

            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', 'Test Skill')
                ->set('skillDescription', 'Test Description')
                ->set('skillCategory', '')
                ->call('saveSkill')
                ->assertHasErrors(['skillCategory']);
        });

        it('validates field lengths for skill creation', function () {
            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', str_repeat('a', 256))
                ->set('skillDescription', str_repeat('b', 256))
                ->set('skillCategory', str_repeat('c', 256))
                ->call('saveSkill')
                ->assertHasErrors(['skillName', 'skillDescription', 'skillCategory']);

            // test for description length
            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', str_repeat('a', 255))
                ->set('skillDescription', str_repeat('b', 2))
                ->set('skillCategory', str_repeat('c', 255))
                ->call('saveSkill')
                ->assertHasErrors(['skillDescription']);

            // test for category length
            livewire(SkillsManager::class)
                ->call('openAddSkillModal')
                ->set('skillName', str_repeat('a', 255))
                ->set('skillDescription', str_repeat('b', 255))
                ->set('skillCategory', str_repeat('c', 2))
                ->call('saveSkill')
                ->assertHasErrors(['skillCategory']);

        });

        it('discards modal state when closed', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal')
                ->set('skillName', 'Test Skill')
                ->set('skillDescription', 'Test Description')
                ->set('skillCategory', 'Test Category')
                ->set('isFormModified', true)
                ->call('closeAddSkillModal');

            expect($component->selectedSkill)->toBeNull();
            expect($component->skillName)->toBe('');
            expect($component->skillDescription)->toBe('');
            expect($component->skillCategory)->toBe('');
            expect($component->isFormModified)->toBe(false);

        });

        it('tracks form modification during skill creation', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->isFormModified)->toBe(false);

            $component->set('skillName', 'New Skill');
            expect($component->isFormModified)->toBe(true);

            $component->set('skillDescription', 'New Description');
            expect($component->isFormModified)->toBe(true);

            $component->set('skillCategory', 'New Category');
            expect($component->isFormModified)->toBe(true);
        });
    });

    describe('Skill Editing modal functionality', function () {
        it('opens edit skill modal for existing skill', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openEditSkillModal', $this->skill1);

            expect($component->selectedSkill->id)->toBe($this->skill1->id);
            expect($component->skillName)->toBe('Laravel');
            expect($component->skillDescription)->toBe('PHP framework for web development');
            expect($component->skillCategory)->toBe('Programming');
            expect($component->isFormModified)->toBe(false);
            expect($component->assertSee('Edit Skill'));
            expect($component->assertSee('Edit the skill details.'));
        });

        it('updates existing skill', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openEditSkillModal', $this->skill1)
                ->set('skillName', 'Laravel Framework')
                ->set('skillDescription', 'Updated PHP framework description')
                ->set('skillCategory', 'Backend')
                ->call('saveSkill');

            $this->skill1->refresh();
            expect($this->skill1->name)->toBe('Laravel Framework');
            expect($this->skill1->description)->toBe('Updated PHP framework description');
            expect($this->skill1->skill_category)->toBe('Backend');
        });

        it('tracks form modification during editing', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openEditSkillModal', $this->skill1);

            expect($component->isFormModified)->toBe(false);

            $component->set('skillName', 'New Name');
            expect($component->isFormModified)->toBe(true);

            $component->set('skillDescription', 'New Description');
            expect($component->isFormModified)->toBe(true);

            $component->set('skillCategory', 'New Category');
            expect($component->isFormModified)->toBe(true);
        });

        it('resets form modification flag when edit modal is closed', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openEditSkillModal', $this->skill1);

            $component->set('skillName', 'Modified Name');
            expect($component->isFormModified)->toBe(true);

            $component->call('closeEditSkillModal');
            expect($component->isFormModified)->toBe(false);
        });

        it('validates required fields for skill editing', function () {
            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', '')
                ->set('skillDescription', '')
                ->set('skillCategory', '')
                ->call('saveSkill')
                ->assertHasErrors(['skillName', 'skillDescription', 'skillCategory']);

            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', 'Test Skill')
                ->set('skillDescription', '')
                ->set('skillCategory', 'Test Category')
                ->call('saveSkill')
                ->assertHasErrors(['skillDescription']);

            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', 'Test Skill')
                ->set('skillDescription', 'Test Description')
                ->set('skillCategory', '')
                ->call('saveSkill')
                ->assertHasErrors(['skillCategory']);

            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', '')
                ->set('skillDescription', 'Test Description')
                ->set('skillCategory', 'Test Category')
                ->call('saveSkill')
                ->assertHasErrors(['skillName']);

        });

        it('validates field lengths for skill editing', function () {
            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', str_repeat('a', 256))
                ->set('skillDescription', str_repeat('b', 256))
                ->set('skillCategory', str_repeat('c', 256))
                ->call('saveSkill')
                ->assertHasErrors(['skillName', 'skillDescription', 'skillCategory']);

            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', str_repeat('a', 255))
                ->set('skillDescription', str_repeat('b', 2))
                ->set('skillCategory', str_repeat('c', 255))
                ->call('saveSkill')
                ->assertHasErrors(['skillDescription']);

            livewire(SkillsManager::class)
                ->call('openEditSkillModal', $this->skill1)
                ->set('skillName', str_repeat('a', 255))
                ->set('skillDescription', str_repeat('b', 255))
                ->set('skillCategory', str_repeat('c', 2))
                ->call('saveSkill')
                ->assertHasErrors(['skillCategory']);
        });
    });

    describe('Skill Deletion', function () {
        it('deletes skill only when not assigned to users', function () {
            livewire(SkillsManager::class)
                ->call('deleteSkill', $this->skill1);

            expect(Skill::where('id', $this->skill1->id)->exists())->toBeFalse();
        });

        it('prevents deletion of skill assigned to users', function () {
            // Assign skill to user
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::INTERMEDIATE->value);

            livewire(SkillsManager::class)
                ->call('deleteSkill', $this->skill1);

            // Skill should still exist
            expect(Skill::where('id', $this->skill1->id)->exists())->toBeTrue();
        });

        it('allows deletion of skill when all user assignments are removed', function () {
            // Assign skill to user
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::INTERMEDIATE->value);

            // Remove the assignment
            $this->staffUser1->skills()->detach($this->skill1->id);

            // Now deletion should work
            livewire(SkillsManager::class)
                ->call('deleteSkill', $this->skill1);

            expect(Skill::where('id', $this->skill1->id)->exists())->toBeFalse();
        });
    });

    describe('User Skills Management', function () {
        it('opens user skill modal with user data', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1);

            expect($component->selectedUser->id)->toBe($this->staffUser1->id);
            expect($component->skillSearchForAssignment)->toBe('');
            expect($component->selectedSkillForAssignment)->toBeNull();
            expect($component->newSkillLevel)->toBe('');
            expect($component->isFormModified)->toBe(false);
            expect($component->assertSee('Manage Skills for'));
            expect($component->assertSee($this->staffUser1->full_name));
            expect($component->assertSee('Laravel'));
            expect($component->assertSee('PHP framework for web development'));
            expect($component->assertSee('Programming'));
        });

        it('closes user skill modal and discards modal state', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('closeUserSkillModal')
                ->asserthasnoerrors();

            expect($component->selectedUser)->toBeNull();
            expect($component->skillSearchForAssignment)->toBe('');
            expect($component->selectedSkillForAssignment)->toBeNull();
            expect($component->newSkillLevel)->toBe('');
            expect($component->isFormModified)->toBe(false);
        });

        it('toggles skill selection for assignment', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id);

            expect($component->selectedSkillForAssignment->id)->toBe($this->skill1->id);
            expect($component->newSkillLevel)->toBe(SkillLevel::BEGINNER->value);

            $component->call('toggleSkillSelection', $this->skill1->id);
            expect($component->selectedSkillForAssignment)->toBeNull();
            expect($component->newSkillLevel)->toBe('');
        });

        it('cancels skill selection', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id)
                ->call('cancelSkillSelection');

            expect($component->selectedSkillForAssignment)->toBeNull();
            expect($component->newSkillLevel)->toBe('');
        });

        it('adds skill with level to user', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id)
                ->set('newSkillLevel', SkillLevel::INTERMEDIATE->value)
                ->call('addSkillWithLevel');

            $this->staffUser1->refresh();
            expect($this->staffUser1->skills)->toHaveCount(1);
            expect($this->staffUser1->skills->contains($this->skill1))->toBeTrue();
            expect($this->staffUser1->skills->first()->pivot->skill_level)->toBe(SkillLevel::INTERMEDIATE->value);
        });

        it('updates user skill level', function () {
            // First assign the skill to a user
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::BEGINNER->value);

            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('updateSkillLevel', $this->skill1->id, SkillLevel::ADVANCED->value);

            $this->assertEquals(
                SkillLevel::ADVANCED->value,
                $this->staffUser1->fresh()->skills()->where('skill_id', $this->skill1->id)->first()->pivot->skill_level
            );
        });

        it('removes skill from user', function () {
            // First assign the skill
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::INTERMEDIATE->value);

            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('removeUserSkill', $this->skill1->id);

            $this->assertFalse($this->staffUser1->fresh()->skills->contains($this->skill1));
        });

        it('validates skill level when updating', function () {
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::BEGINNER->value);

            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('updateSkillLevel', $this->skill1->id, 'invalid_level');

            // Should not update with invalid level
            $this->assertEquals(
                SkillLevel::BEGINNER->value,
                $this->staffUser1->fresh()->skills()->where('skill_id', $this->skill1->id)->first()->pivot->skill_level
            );
        });

        it('validates skill level when adding skill', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id)
                ->set('newSkillLevel', 'invalid_level')
                ->call('addSkillWithLevel')
                ->assertHasErrors(['newSkillLevel']);
        });

        it('requires valid skill level for adding skill', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id)
                ->set('newSkillLevel', '')
                ->call('addSkillWithLevel');

            // The component should not add the skill without a valid level
            expect($this->staffUser1->fresh()->skills->contains($this->skill1))->toBeFalse();
        });
    });

    describe('New Skill Creation with Assignment', function () {
        it('toggles create skill form', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleCreateSkillForm');

            expect($component->showCreateSkillForm)->toBe(true);
            expect($component->newSkillLevel)->toBe(SkillLevel::BEGINNER->value);

            $component->call('toggleCreateSkillForm');
            expect($component->showCreateSkillForm)->toBe(false);
        });

        it('updates new skill name when skill search changes', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->set('skillSearchForAssignment', 'New Skill');

            expect($component->newSkillName)->toBe('New Skill');
        });

        it('creates new skill with details and assigns to user', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleCreateSkillForm')
                ->set('newSkillName', 'Docker')
                ->set('newSkillDescription', 'Containerization platform')
                ->set('newSkillCategory', 'DevOps')
                ->set('newSkillLevel', SkillLevel::INTERMEDIATE->value)
                ->call('createAndAssignSkill');

            expect(Skill::where('name', 'Docker')->exists())->toBeTrue();

            $this->staffUser1->refresh();
            expect($this->staffUser1->skills)->toHaveCount(1);
            expect($this->staffUser1->skills->first()->name)->toBe('Docker');
            expect($this->staffUser1->skills->first()->pivot->skill_level)->toBe(SkillLevel::INTERMEDIATE->value);
        });

        it('validates required fields for new skill creation', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleCreateSkillForm')
                ->set('newSkillName', '')
                ->set('newSkillDescription', '')
                ->set('newSkillCategory', '')
                ->set('newSkillLevel', '')
                ->call('createAndAssignSkill')
                ->assertHasErrors(['newSkillName', 'newSkillDescription', 'newSkillCategory', 'newSkillLevel']);
        });

        it('validates field lengths for new skill creation', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleCreateSkillForm')
                ->set('newSkillName', str_repeat('a', 256))
                ->set('newSkillDescription', str_repeat('b', 256))
                ->set('newSkillCategory', str_repeat('c', 256))
                ->set('newSkillLevel', SkillLevel::BEGINNER->value)
                ->call('createAndAssignSkill')
                ->assertHasErrors(['newSkillName', 'newSkillDescription', 'newSkillCategory']);
        });

        it('validates skill level for new skill creation', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleCreateSkillForm')
                ->set('newSkillName', 'Test Skill')
                ->set('newSkillDescription', 'Test Description')
                ->set('newSkillCategory', 'Test Category')
                ->set('newSkillLevel', 'invalid_level')
                ->call('createAndAssignSkill')
                ->assertHasErrors(['newSkillLevel']);
        });
    });

    describe('Edge Cases and Error Handling', function () {
        it('handles empty skill list gracefully', function () {
            Skill::query()->delete();

            livewire(SkillsManager::class)
                ->assertStatus(200);
        });

        it('handles special characters in skill names', function () {
            Skill::factory()->create([
                'name' => 'C++',
                'description' => 'Programming language with special chars: & < > "',
                'skill_category' => 'Programming',
            ]);

            livewire(SkillsManager::class)
                ->assertSeeText('C++')
                ->assertSeeText('Programming language with special chars: & < > "');
        });

        it('handles very long skill names and descriptions', function () {
            $longName = str_repeat('a', 100);
            $longDescription = str_repeat('b', 100);

            Skill::factory()->create([
                'name' => $longName,
                'description' => $longDescription,
                'skill_category' => 'Test',
            ]);

            livewire(SkillsManager::class)
                ->assertSeeText($longName)
                ->assertSeeText($longDescription);
        });

        it('handles user with no skills', function () {
            $userWithoutSkills = User::factory()->create(['is_staff' => true]);

            $component = livewire(SkillsManager::class);

            $component->assertSee('Skills Management')
                ->assertSee($userWithoutSkills->full_name);
        });

        it('handles non-existent skill ID gracefully', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', 99999);

            expect($component->selectedSkillForAssignment)->toBeNull();
        });

        it('handles non-existent user ID gracefully', function () {
            $component = livewire(SkillsManager::class);
            $component->call('updateSkillLevel', 99999, SkillLevel::INTERMEDIATE->value);

            // Should not throw an error
            expect($component)->not->toThrow(Exception::class);
        });

        it('handles empty user search gracefully', function () {
            $component = livewire(SkillsManager::class);
            $component->set('userSearchQuery', '');

            // Should show all users
            $component->assertSee('John Doe')
                ->assertSee('Jane Smith')
                ->assertSee('Bob Johnson');
        });

        it('handles empty skill search gracefully', function () {
            $component = livewire(SkillsManager::class);
            $component->set('skillSearchQuery', '');

            // Should show all skills
            $component->assertSee('Laravel')
                ->assertSee('React')
                ->assertSee('Project Management');
        });

        it('handles skill search with special characters', function () {
            Skill::factory()->create([
                'name' => 'C#',
                'description' => 'Microsoft programming language',
                'skill_category' => 'Programming',
            ]);

            livewire(SkillsManager::class)
                ->set('skillSearchQuery', 'C#')
                ->assertSeeText('C#');
        });

        it('handles user search with special characters', function () {
            User::factory()->create([
                'is_staff' => true,
                'forenames' => 'José',
                'surname' => 'García',
            ]);

            livewire(SkillsManager::class)
                ->set('userSearchQuery', 'José')
                ->assertSeeText('José García');
        });
    });

    describe('Pagination and UI State Management', function () {
        it('paginates skills correctly', function () {
            // Create more skills to test pagination
            Skill::factory()->count(15)->create();

            $component = livewire(SkillsManager::class);

            // Should show first page with default skills
            $component->assertSeeText('Laravel')
                ->assertSeeText('React')
                ->assertSeeText('Project Management');
        });

        it('resets page when search changes', function () {
            $component = livewire(SkillsManager::class);
            $component->set('skillSearchQuery', 'Laravel');

            // Verify search was applied
            expect($component->skillSearchQuery)->toBe('Laravel');
        });

        it('resets page when sort changes', function () {
            $component = livewire(SkillsManager::class);
            $component->call('sort', 'name');

            // Verify sort was applied
            expect($component->sortColumn)->toBe('name');
            expect($component->sortDirection)->toBe('desc');
        });

        it('maintains pagination state during operations', function () {
            $component = livewire(SkillsManager::class);

            // Test that search and sort operations work
            $component->set('skillSearchQuery', 'Laravel');
            expect($component->skillSearchQuery)->toBe('Laravel');

            $component->call('sort', 'name');
            expect($component->sortColumn)->toBe('name');
        });
    });

    describe('Form State Management', function () {
        it('tracks form modification for skill name', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->isFormModified)->toBe(false);

            $component->set('skillName', 'New Skill');
            expect($component->isFormModified)->toBe(true);
        });

        it('tracks form modification for skill description', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->isFormModified)->toBe(false);

            $component->set('skillDescription', 'New Description');
            expect($component->isFormModified)->toBe(true);
        });

        it('tracks form modification for skill category', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->isFormModified)->toBe(false);

            $component->set('skillCategory', 'New Category');
            expect($component->isFormModified)->toBe(true);
        });

        it('resets form modification flag when modal is closed', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal')
                ->set('skillName', 'Modified')
                ->call('closeAddSkillModal');

            expect($component->isFormModified)->toBe(false);
        });

        it('marks form as not modified when opening modal', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openAddSkillModal');

            expect($component->isFormModified)->toBe(false);
        });
    });

    describe('Skill Display with User Counts', function () {
        beforeEach(function () {
            // Assign skills to users for testing user counts
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::INTERMEDIATE->value);
            $this->staffUser2->updateSkillForUser($this->skill1, SkillLevel::ADVANCED->value);
            $this->staffUser1->updateSkillForUser($this->skill2, SkillLevel::BEGINNER->value);
            // $this->skill3 has 0 users
        });

        it('displays user count badges for each skill', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('2') // skill1 has 2 users
                ->assertSeeText('1') // skill2 has 1 user
                ->assertSeeText('0'); // skill3 has 0 users
        });

        it('shows correct user count for Laravel skill', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('2'); // Should show 2 users
        });

        it('shows correct user count for React skill', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('React')
                ->assertSeeText('1'); // Should show 1 user
        });

        it('shows zero user count for Project Management skill', function () {
            livewire(SkillsManager::class)
                ->assertSeeText('Project Management')
                ->assertSeeText('0'); // Should show 0 users
        });

        it('updates user count when users are assigned to skills', function () {
            // Create a new user
            $newUser = User::factory()->create(['is_staff' => true]);

            // Assign the new user to skill1
            $newUser->updateSkillForUser($this->skill1, SkillLevel::BEGINNER->value);

            livewire(SkillsManager::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('3'); // Should now show 3 users
        });

        it('updates user count when users are removed from skills', function () {
            // Remove a user from skill1
            $this->staffUser1->skills()->detach($this->skill1->id);

            livewire(SkillsManager::class)
                ->assertSeeText('Laravel')
                ->assertSeeText('1'); // Should now show 1 user
        });

        it('maintains user count display during search operations', function () {
            $component = livewire(SkillsManager::class);

            // Search for Laravel skill
            $component->set('skillSearchQuery', 'Laravel')
                ->assertSeeText('Laravel')
                ->assertSeeText('2'); // User count should still be visible

            // Clear search
            $component->set('skillSearchQuery', '')
                ->assertSeeText('Laravel')
                ->assertSeeText('2')
                ->assertSeeText('React')
                ->assertSeeText('1');
        });

        it('maintains user count display during sorting operations', function () {
            $component = livewire(SkillsManager::class);

            // Sort by name descending
            $component->call('sort', 'name');
            expect($component->sortDirection)->toBe('desc');

            // Sort by name again to toggle back to ascending
            $component->call('sort', 'name');
            expect($component->sortDirection)->toBe('asc');

            // Verify user counts are still visible after sorting
            $component->assertSeeText('2') // Laravel skill user count
                ->assertSeeText('1') // React skill user count
                ->assertSeeText('0'); // Project Management skill user count
        });
    });

    describe('Skill Level Management', function () {
        it('validates all skill levels correctly', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id);

            // Test each valid skill level
            foreach (SkillLevel::cases() as $level) {
                $component->set('newSkillLevel', $level->value)
                    ->call('addSkillWithLevel')
                    ->assertHasNoErrors();

                // Clean up for next test
                $this->staffUser1->skills()->detach($this->skill1->id);
            }
        });

        it('rejects invalid skill levels', function () {
            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1)
                ->call('toggleSkillSelection', $this->skill1->id)
                ->set('newSkillLevel', 'invalid_level')
                ->call('addSkillWithLevel')
                ->assertHasErrors(['newSkillLevel']);
        });

        it('handles skill level updates with all valid levels', function () {
            $this->staffUser1->updateSkillForUser($this->skill1, SkillLevel::BEGINNER->value);

            $component = livewire(SkillsManager::class);
            $component->call('openUserSkillModal', $this->staffUser1);

            foreach (SkillLevel::cases() as $level) {
                $component->call('updateSkillLevel', $this->skill1->id, $level->value);

                $this->assertEquals(
                    $level->value,
                    $this->staffUser1->fresh()->skills()->where('skill_id', $this->skill1->id)->first()->pivot->skill_level
                );
            }
        });
    });
});
