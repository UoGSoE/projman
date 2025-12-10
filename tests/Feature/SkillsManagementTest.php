<?php

use App\Enums\SkillLevel;
use App\Livewire\SkillsManager;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

// =============================================================================
// Component Basics
// =============================================================================

it('renders the skills manager page', function () {
    livewire(SkillsManager::class)
        ->assertStatus(200)
        ->assertSee('Skills Management');
});

it('shows available skills tab by default', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel']);

    livewire(SkillsManager::class)
        ->assertSee('Laravel');
});

// =============================================================================
// Skill CRUD
// =============================================================================

it('creates a new skill', function () {
    livewire(SkillsManager::class)
        ->call('openAddSkillModal')
        ->set('skillName', 'Docker')
        ->set('skillDescription', 'Containerization platform')
        ->set('skillCategory', 'DevOps')
        ->call('saveSkill')
        ->assertHasNoErrors();

    expect(Skill::where('name', 'Docker')->exists())->toBeTrue();
});

it('validates skill creation', function (array $data, array $errors) {
    livewire(SkillsManager::class)
        ->call('openAddSkillModal')
        ->set('skillName', $data['skillName'] ?? 'Valid Name')
        ->set('skillDescription', $data['skillDescription'] ?? 'Valid description')
        ->set('skillCategory', $data['skillCategory'] ?? 'Valid Category')
        ->call('saveSkill')
        ->assertHasErrors($errors);
})->with([
    'empty name' => [['skillName' => ''], ['skillName']],
    'empty description' => [['skillDescription' => ''], ['skillDescription']],
    'empty category' => [['skillCategory' => ''], ['skillCategory']],
    'name too long' => [['skillName' => str_repeat('a', 256)], ['skillName']],
    'description too short' => [['skillDescription' => 'ab'], ['skillDescription']],
    'category too short' => [['skillCategory' => 'ab'], ['skillCategory']],
]);

it('edits an existing skill', function () {
    $skill = Skill::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old description',
        'skill_category' => 'Old Category',
    ]);

    livewire(SkillsManager::class)
        ->call('openEditSkillModal', $skill)
        ->set('skillName', 'New Name')
        ->set('skillDescription', 'New description')
        ->set('skillCategory', 'New Category')
        ->call('saveSkill')
        ->assertHasNoErrors();

    $skill->refresh();
    expect($skill->name)->toBe('New Name');
    expect($skill->description)->toBe('New description');
    expect($skill->skill_category)->toBe('New Category');
});

it('deletes a skill without users', function () {
    $skill = Skill::factory()->create();
    $skillId = $skill->id;

    livewire(SkillsManager::class)
        ->call('deleteSkill', $skill);

    expect(Skill::find($skillId))->toBeNull();
});

it('prevents deleting skill with assigned users', function () {
    $skill = Skill::factory()->create();
    $user = User::factory()->create(['is_staff' => true]);
    $user->updateSkill($skill->id, SkillLevel::INTERMEDIATE->value);

    livewire(SkillsManager::class)
        ->call('deleteSkill', $skill);

    expect(Skill::find($skill->id))->not->toBeNull();
});

// =============================================================================
// Search & Filter
// =============================================================================

it('filters skills by search query', function (string $search, string $shouldSee, string $shouldNotSee) {
    Skill::factory()->create(['name' => 'Laravel', 'description' => 'PHP framework', 'skill_category' => 'Programming']);
    Skill::factory()->create(['name' => 'React', 'description' => 'JS library', 'skill_category' => 'Frontend']);

    livewire(SkillsManager::class)
        ->set('skillSearchQuery', $search)
        ->assertSee($shouldSee)
        ->assertDontSee($shouldNotSee);
})->with([
    'by name' => ['Laravel', 'Laravel', 'React'],
    'by description' => ['PHP framework', 'Laravel', 'React'],
    'by category' => ['Programming', 'Laravel', 'React'],
    'case insensitive' => ['laravel', 'Laravel', 'React'],
]);

it('filters users by search query', function (string $search, string $shouldSee, string $shouldNotSee) {
    User::factory()->create(['is_staff' => true, 'forenames' => 'John', 'surname' => 'Doe']);
    User::factory()->create(['is_staff' => true, 'forenames' => 'Jane', 'surname' => 'Smith']);

    livewire(SkillsManager::class)
        ->set('userSearchQuery', $search)
        ->assertSee($shouldSee)
        ->assertDontSee($shouldNotSee);
})->with([
    'by forename' => ['John', 'John Doe', 'Jane Smith'],
    'by surname' => ['Smith', 'Jane Smith', 'John Doe'],
    'full name' => ['John Doe', 'John Doe', 'Jane Smith'],
    'case insensitive' => ['john', 'John Doe', 'Jane Smith'],
]);

it('requires minimum 2 characters for search', function () {
    Skill::factory()->create(['name' => 'Laravel']);
    Skill::factory()->create(['name' => 'React']);

    livewire(SkillsManager::class)
        ->set('skillSearchQuery', 'L')
        ->assertSee('Laravel')
        ->assertSee('React');
});

// =============================================================================
// Sorting
// =============================================================================

it('sorts skills by column', function () {
    Skill::factory()->create(['name' => 'Zebra']);
    Skill::factory()->create(['name' => 'Apple']);

    livewire(SkillsManager::class)
        ->assertSeeInOrder(['Apple', 'Zebra']);
});

it('toggles sort direction when clicking same column', function () {
    Skill::factory()->create(['name' => 'Zebra']);
    Skill::factory()->create(['name' => 'Apple']);

    livewire(SkillsManager::class)
        ->call('sort', 'name')
        ->assertSeeInOrder(['Zebra', 'Apple'])
        ->call('sort', 'name')
        ->assertSeeInOrder(['Apple', 'Zebra']);
});

// =============================================================================
// User Skill Assignment
// =============================================================================

it('assigns skill to user with level', function () {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create();

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('toggleSkillSelection', $skill->id)
        ->set('newSkillLevel', SkillLevel::INTERMEDIATE->value)
        ->call('addSkillWithLevel');

    $user->refresh();
    expect($user->skills)->toHaveCount(1);
    expect($user->skills->first()->pivot->skill_level)->toBe(SkillLevel::INTERMEDIATE->value);
});

it('updates user skill level', function () {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create();
    $user->updateSkill($skill->id, SkillLevel::BEGINNER->value);

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('updateSkillLevel', $skill->id, SkillLevel::ADVANCED->value);

    expect($user->fresh()->skills->first()->pivot->skill_level)->toBe(SkillLevel::ADVANCED->value);
});

it('removes skill from user', function () {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create();
    $user->updateSkill($skill->id, SkillLevel::INTERMEDIATE->value);

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('removeUserSkill', $skill->id);

    expect($user->fresh()->skills)->toHaveCount(0);
});

it('validates skill level', function (string $invalidLevel) {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create();

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('toggleSkillSelection', $skill->id)
        ->set('newSkillLevel', $invalidLevel)
        ->call('addSkillWithLevel')
        ->assertHasErrors(['newSkillLevel']);

    expect($user->fresh()->skills)->toHaveCount(0);
})->with(['invalid', 'not_a_level', '999']);

// =============================================================================
// Inline Skill Creation
// =============================================================================

it('creates new skill while assigning to user', function () {
    $user = User::factory()->create(['is_staff' => true]);

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('toggleCreateSkillForm')
        ->set('newSkillName', 'Docker')
        ->set('newSkillDescription', 'Container platform')
        ->set('newSkillCategory', 'DevOps')
        ->set('newSkillLevel', SkillLevel::INTERMEDIATE->value)
        ->call('createAndAssignSkill');

    expect(Skill::where('name', 'Docker')->exists())->toBeTrue();
    expect($user->fresh()->skills)->toHaveCount(1);
    expect($user->fresh()->skills->first()->name)->toBe('Docker');
});

it('validates inline skill creation', function () {
    $user = User::factory()->create(['is_staff' => true]);

    livewire(SkillsManager::class)
        ->call('openUserSkillModal', $user)
        ->call('toggleCreateSkillForm')
        ->set('newSkillName', '')
        ->set('newSkillDescription', '')
        ->set('newSkillCategory', '')
        ->set('newSkillLevel', '')
        ->call('createAndAssignSkill')
        ->assertHasErrors(['newSkillName', 'newSkillDescription', 'newSkillCategory', 'newSkillLevel']);

    expect(Skill::count())->toBe(0);
});

// =============================================================================
// Export
// =============================================================================

it('exports skills to file', function (string $format, string $method) {
    Skill::factory()->create(['name' => 'Test Skill']);

    livewire(SkillsManager::class)
        ->set('activeTab', 'available-skills')
        ->call($method)
        ->assertFileDownloaded('skills-export-'.now()->format('Y-m-d').'.'.$format);
})->with([
    'xlsx' => ['xlsx', 'downloadExcel'],
    'csv' => ['csv', 'downloadCsv'],
]);

it('exports user skills to file', function (string $format, string $method) {
    $user = User::factory()->create(['is_staff' => true]);
    $skill = Skill::factory()->create();
    $user->updateSkill($skill->id, SkillLevel::INTERMEDIATE->value);

    livewire(SkillsManager::class)
        ->set('activeTab', 'user-skills')
        ->call($method)
        ->assertFileDownloaded('user-skills-export-'.now()->format('Y-m-d').'.'.$format);
})->with([
    'xlsx' => ['xlsx', 'downloadExcel'],
    'csv' => ['csv', 'downloadCsv'],
]);

// =============================================================================
// User Count Display
// =============================================================================

it('shows correct user count per skill', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $user1 = User::factory()->create(['is_staff' => true]);
    $user2 = User::factory()->create(['is_staff' => true]);
    $user1->updateSkill($skill->id, SkillLevel::BEGINNER->value);
    $user2->updateSkill($skill->id, SkillLevel::ADVANCED->value);

    $component = livewire(SkillsManager::class);

    // Verify via export data which is more reliable than assertSeeText
    $exportData = $component->instance()->getSkillsExportData();
    $laravelRow = collect($exportData)->first(fn ($row) => $row[0] === 'Laravel');
    expect($laravelRow[3])->toBe(2);
});

it('updates count when users assigned or removed', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $user = User::factory()->create(['is_staff' => true]);

    // Initially 0 users
    $component = livewire(SkillsManager::class);
    $exportData = $component->instance()->getSkillsExportData();
    $row = collect($exportData)->first(fn ($r) => $r[0] === 'Laravel');
    expect($row[3])->toBe(0);

    // Assign user
    $user->updateSkill($skill->id, SkillLevel::BEGINNER->value);

    $component = livewire(SkillsManager::class);
    $exportData = $component->instance()->getSkillsExportData();
    $row = collect($exportData)->first(fn ($r) => $r[0] === 'Laravel');
    expect($row[3])->toBe(1);

    // Remove user
    $user->removeSkill($skill->id);

    $component = livewire(SkillsManager::class);
    $exportData = $component->instance()->getSkillsExportData();
    $row = collect($exportData)->first(fn ($r) => $r[0] === 'Laravel');
    expect($row[3])->toBe(0);
});
