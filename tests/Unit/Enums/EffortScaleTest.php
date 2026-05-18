<?php

use App\Enums\EffortScale;

it('exposes an estimated person-days figure for each case', function () {
    expect(EffortScale::SMALL->estimatedDays())->toBe(5)
        ->and(EffortScale::MEDIUM->estimatedDays())->toBe(10)
        ->and(EffortScale::LARGE->estimatedDays())->toBe(40)
        ->and(EffortScale::X_LARGE->estimatedDays())->toBe(75)
        ->and(EffortScale::XX_LARGE->estimatedDays())->toBe(150);
});
