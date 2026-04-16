<?php

use App\Services\SkillsSpreadsheetParser;

it('parses skills from the baseline sheet', function () {
    $fixturePath = __DIR__.'/../fixtures/it_training_modeller.xlsx';

    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse($fixturePath);

    expect($result)->toHaveKeys(['skills', 'staffSkills', 'skippedStaff']);
    expect($result['skills'])->toHaveCount(88);

    $firstSkill = collect($result['skills'])->firstWhere('name', 'Asset Management');
    expect($firstSkill)->not->toBeNull();
    expect($firstSkill['code'])->toBe('CoSECore1');
    expect($firstSkill['category'])->toBe('Core');
    expect($firstSkill['description'])->toContain('life cycle of assets');
});

it('maps skill codes to correct categories', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');
    $skills = collect($result['skills']);

    expect($skills->firstWhere('name', 'Asset Management')['category'])->toBe('Core');
    expect($skills->firstWhere('name', 'Availability Management')['category'])->toBe('Service');
    expect($skills->firstWhere('name', 'Digital Forensics')['category'])->toBe('Security');
    expect($skills->firstWhere('name', 'Competency assessment')['category'])->toBe('Management');
    expect($skills->firstWhere('name', 'Audit')['category'])->toBe('Governance');
    expect($skills->firstWhere('name', 'Acceptance testing')['category'])->toBe('Technical');
    expect($skills->firstWhere('name', 'Benefits management')['category'])->toBe('Business');
});

it('filters out legend rows from the baseline sheet', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');
    $skillNames = collect($result['skills'])->pluck('name');

    expect($skillNames)->not->toContain('Competence Level');
    expect($skillNames)->not->toContain('No Knowledge');
    expect($skillNames)->not->toContain('Score');
});

it('assigns General category to skills with no code', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');
    $generalSkills = collect($result['skills'])->where('category', 'General');

    expect($generalSkills)->not->toBeEmpty();
    expect($generalSkills->pluck('name'))->toContain('Systems integration and build');
});

it('parses staff skills from the master sheet', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');

    // 34 total staff, 9 have all-99s so 25 with importable skills
    expect(count($result['staffSkills']))->toBe(25);
    expect($result['staffSkills'])->toHaveKey('John Watson');
    expect($result['staffSkills']['John Watson'])->not->toBeEmpty();
});

it('tracks skipped staff with all-99 assessments', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');

    expect($result['skippedStaff'])->toContain('Neville Parker');
    expect($result['skippedStaff'])->toContain('Vera Stanhope');
    expect($result['skippedStaff'])->toHaveCount(9);
});

it('maps actual values to correct skill level strings', function () {
    $parser = new SkillsSpreadsheetParser;
    $result = $parser->parse(__DIR__.'/../fixtures/it_training_modeller.xlsx');

    $validLevels = ['awareness', 'working', 'practitioner', 'expert'];
    foreach ($result['staffSkills'] as $staffName => $skills) {
        foreach ($skills as $skillName => $level) {
            expect($validLevels)->toContain($level);
        }
    }
});
