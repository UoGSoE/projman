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

it('renders the skills manager page', function () {
    livewire(SkillsManager::class)
        ->assertStatus(200)
        ->assertSee('Skills Management');
});

it('shows available skills in the table', function () {
    Skill::factory()->create(['name' => 'Laravel']);

    livewire(SkillsManager::class)
        ->assertSee('Laravel');
});

it('filters skills by search query', function () {
    Skill::factory()->create(['name' => 'Laravel', 'description' => 'PHP framework', 'skill_category' => 'Technical']);
    Skill::factory()->create(['name' => 'React', 'description' => 'JS library', 'skill_category' => 'Technical']);

    livewire(SkillsManager::class)
        ->set('skillSearchQuery', 'Laravel')
        ->assertSee('Laravel')
        ->assertDontSee('React');
});

it('sorts skills by column', function () {
    Skill::factory()->create(['name' => 'Zebra']);
    Skill::factory()->create(['name' => 'Apple']);

    livewire(SkillsManager::class)
        ->assertSeeInOrder(['Apple', 'Zebra'])
        ->call('sort', 'name')
        ->assertSeeInOrder(['Zebra', 'Apple']);
});

it('exports skills to xlsx', function () {
    Skill::factory()->create(['name' => 'Test Skill']);

    livewire(SkillsManager::class)
        ->set('activeTab', 'available-skills')
        ->call('downloadExcel')
        ->assertFileDownloaded('skills-export-'.now()->format('Y-m-d').'.xlsx');
});

it('shows correct user count per skill', function () {
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $user1 = User::factory()->create(['is_staff' => true]);
    $user2 = User::factory()->create(['is_staff' => true]);
    $user1->updateSkill($skill->id, SkillLevel::AWARENESS->value);
    $user2->updateSkill($skill->id, SkillLevel::EXPERT->value);

    $component = livewire(SkillsManager::class);
    $exportData = $component->instance()->getSkillsExportData();
    $laravelRow = collect($exportData)->first(fn ($row) => $row[0] === 'Laravel');
    expect($laravelRow[3])->toBe(2);
});
