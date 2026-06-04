<?php

use App\Enums\SkillLevel;

it('exposes a display name for each case', function () {
    expect(SkillLevel::NO_KNOWLEDGE->getDisplayName())->toBe('No Knowledge')
        ->and(SkillLevel::AWARENESS->getDisplayName())->toBe('Awareness')
        ->and(SkillLevel::WORKING->getDisplayName())->toBe('Working')
        ->and(SkillLevel::PRACTITIONER->getDisplayName())->toBe('Practitioner')
        ->and(SkillLevel::EXPERT->getDisplayName())->toBe('Expert');
});

it('exposes an ascending numeric value for each case', function () {
    expect(SkillLevel::NO_KNOWLEDGE->getNumericValue())->toBe(0)
        ->and(SkillLevel::AWARENESS->getNumericValue())->toBe(1)
        ->and(SkillLevel::WORKING->getNumericValue())->toBe(2)
        ->and(SkillLevel::PRACTITIONER->getNumericValue())->toBe(3)
        ->and(SkillLevel::EXPERT->getNumericValue())->toBe(4);
});

it('exposes a Flux colour for each case', function () {
    expect(SkillLevel::NO_KNOWLEDGE->colour())->toBe('zinc')
        ->and(SkillLevel::AWARENESS->colour())->toBe('blue')
        ->and(SkillLevel::WORKING->colour())->toBe('green')
        ->and(SkillLevel::PRACTITIONER->colour())->toBe('amber')
        ->and(SkillLevel::EXPERT->colour())->toBe('orange');
});

it('lists the storable level values, excluding No Knowledge', function () {
    expect(SkillLevel::getAll())->toBe(['awareness', 'working', 'practitioner', 'expert']);
});
