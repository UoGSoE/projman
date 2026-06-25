<?php

use App\Enums\EffortScale;

it('exposes an estimated person-days figure for each case', function () {
    expect(EffortScale::SMALL->estimatedDays())->toBe(5)
        ->and(EffortScale::MEDIUM->estimatedDays())->toBe(10)
        ->and(EffortScale::LARGE->estimatedDays())->toBe(40)
        ->and(EffortScale::X_LARGE->estimatedDays())->toBe(75)
        ->and(EffortScale::XX_LARGE->estimatedDays())->toBe(150);
});

it('exposes a human-readable label for each case', function () {
    expect(EffortScale::SMALL->label())->toBe('Small (≤5 days)')
        ->and(EffortScale::MEDIUM->label())->toBe('Medium (6-15 days)')
        ->and(EffortScale::LARGE->label())->toBe('Large (30-50 days)')
        ->and(EffortScale::X_LARGE->label())->toBe('X-Large (51-100 days)')
        ->and(EffortScale::XX_LARGE->label())->toBe('XX-Large (>101 days)');
});

it('exposes a days range for each case', function () {
    expect(EffortScale::SMALL->daysRange())->toBe('≤5')
        ->and(EffortScale::MEDIUM->daysRange())->toBe('6-15')
        ->and(EffortScale::LARGE->daysRange())->toBe('30-50')
        ->and(EffortScale::X_LARGE->daysRange())->toBe('51-100')
        ->and(EffortScale::XX_LARGE->daysRange())->toBe('>101');
});
