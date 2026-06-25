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

it('forbids a non-admin from calling confirmImport via Livewire', function () {
    $user = User::factory()->create(['is_admin' => false, 'is_staff' => true]);
    $target = User::factory()->create(['is_staff' => true, 'surname' => 'Target', 'forenames' => 'User']);

    $this->actingAs($user);

    Livewire\Livewire::test(SkillsImporter::class)
        ->set('step', 'preview')
        ->set('parsedSkills', [['name' => 'Sneaky Skill', 'description' => 'd', 'category' => 'c']])
        ->call('confirmImport')
        ->assertForbidden();

    expect(Skill::where('name', 'Sneaky Skill')->exists())->toBeFalse();
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

    // Watson's levels reflect the spreadsheet's numeric values mapped to SkillLevel
    // (Asset Management = 3 -> practitioner, Information security = 2 -> working).
    $watson->refresh();
    expect($watson->skills)->not->toBeEmpty();
    expect($watson->getSkillLevel(Skill::where('name', 'Asset Management')->first()))->toBe('practitioner');
    expect($watson->getSkillLevel(Skill::where('name', 'Information security')->first()))->toBe('working');
});

it('overwrites existing skill levels and removes stale skills on re-import', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $watson = User::factory()->create(['is_staff' => true, 'surname' => 'Watson', 'forenames' => 'John']);
    $this->actingAs($admin);

    // Pre-existing data: Asset Management at the wrong level, plus a skill that is NOT
    // in the spreadsheet and should be dropped by the sync.
    $assetMgmt = Skill::factory()->create(['name' => 'Asset Management']);
    $staleSkill = Skill::factory()->create(['name' => 'Obsolete Skill']);
    $watson->skills()->attach($assetMgmt->id, ['skill_level' => 'awareness']);
    $watson->skills()->attach($staleSkill->id, ['skill_level' => 'expert']);

    $file = UploadedFile::fake()->createWithContent(
        'it_training_modeller.xlsx',
        file_get_contents(__DIR__.'/../fixtures/it_training_modeller.xlsx')
    );

    Livewire::test(SkillsImporter::class)
        ->set('spreadsheet', $file)
        ->call('parseSpreadsheet')
        ->call('confirmImport')
        ->assertSet('step', 'complete');

    $watson->refresh();

    // The matching skill is overwritten to the spreadsheet's level (awareness -> practitioner)...
    expect($watson->getSkillLevel($assetMgmt))->toBe('practitioner');
    // ...and the skill absent from the spreadsheet is removed by the sync, not left behind.
    expect($watson->skills->pluck('name'))->not->toContain('Obsolete Skill');
    expect($watson->getSkillLevel($staleSkill))->toBe('none');
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
    $marple = User::factory()->create(['is_staff' => true, 'surname' => 'Marple', 'forenames' => 'Jane']);
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

    // Control: an unrelated unambiguous match still auto-matches, so a bug dumping
    // everyone into unmatched would fail this test.
    expect($component->get('autoMatched'))->toHaveKey('Jane Marple');
    expect($component->get('autoMatched')['Jane Marple']['userId'])->toBe($marple->id);
});
