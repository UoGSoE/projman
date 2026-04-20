<?php

use App\Livewire\SkillsImporter;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

it('requires admin access', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_staff' => true]);

    $this->actingAs($user)
        ->get(route('skills.import'))
        ->assertForbidden();
});

it('renders the upload step by default', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);

    $this->actingAs($admin)
        ->get(route('skills.import'))
        ->assertOk()
        ->assertSee('Upload SFIA Spreadsheet');
});

it('matches users by surname when parsing the spreadsheet', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $watson = User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    $this->actingAs($admin);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    $component = Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet')
        ->assertSet('step', 'preview');

    expect($component->get('autoMatched'))->toHaveKey('John Watson');
    expect($component->get('autoMatched')['John Watson']['userId'])->toBe($watson->id);
});

it('imports skills and syncs user skill levels on confirm', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $watson = User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    $this->actingAs($admin);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet')
        ->call('confirmImport')
        ->assertSet('step', 'complete');

    // Skills should be imported
    expect(Skill::count())->toBe(88);
    expect(Skill::where('name', 'Asset Management')->exists())->toBeTrue();
    expect(Skill::where('name', 'Asset Management')->first()->skill_category)->toBe('Core');

    // Watson should have skills assigned
    $watson->refresh();
    expect($watson->skills)->not->toBeEmpty();

    // Verify a specific skill level
    $assetMgmt = Skill::where('name', 'Asset Management')->first();
    $watsonSkillLevel = $watson->getSkillLevel($assetMgmt);
    expect($watsonSkillLevel)->not->toBe('none');
});

it('overwrites existing skill levels on re-import', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $watson = User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    $this->actingAs($admin);

    // Pre-populate with old data
    $oldSkill = Skill::factory()->create(['name' => 'Asset Management']);
    $watson->skills()->attach($oldSkill->id, ['skill_level' => 'awareness']);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet')
        ->call('confirmImport')
        ->assertSet('step', 'complete');

    // Watson's skills should be replaced (sync), not just appended
    $watson->refresh();
    $assetMgmt = Skill::where('name', 'Asset Management')->first();
    $watsonLevel = $watson->getSkillLevel($assetMgmt);
    expect($watsonLevel)->not->toBe('awareness'); // Should be updated from the spreadsheet
});

it('falls back to forename and surname when a surname has duplicates', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $johnWatson = User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'Mary']);
    $this->actingAs($admin);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    $component = Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet');

    expect($component->get('autoMatched'))->toHaveKey('John Watson');
    expect($component->get('autoMatched')['John Watson']['userId'])->toBe($johnWatson->id);
});

it('leaves a spreadsheet name unmatched when forename and surname are also ambiguous', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    $this->actingAs($admin);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    $component = Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet');

    expect($component->get('autoMatched'))->not->toHaveKey('John Watson');
    expect($component->get('unmatched'))->toContain('John Watson');
});
