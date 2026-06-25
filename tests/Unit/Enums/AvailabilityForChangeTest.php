<?php

use App\Enums\AvailabilityForChange;

it('has the expected cases with percentage values', function () {
    expect(AvailabilityForChange::None->value)->toBe(0)
        ->and(AvailabilityForChange::Minimal->value)->toBe(20)
        ->and(AvailabilityForChange::Low->value)->toBe(40)
        ->and(AvailabilityForChange::Moderate->value)->toBe(60)
        ->and(AvailabilityForChange::Good->value)->toBe(80)
        ->and(AvailabilityForChange::Full->value)->toBe(100);
});

it('exposes a label for each case as a percentage string', function () {
    expect(AvailabilityForChange::None->label())->toBe('0%')
        ->and(AvailabilityForChange::Minimal->label())->toBe('20%')
        ->and(AvailabilityForChange::Low->label())->toBe('40%')
        ->and(AvailabilityForChange::Moderate->label())->toBe('60%')
        ->and(AvailabilityForChange::Good->label())->toBe('80%')
        ->and(AvailabilityForChange::Full->label())->toBe('100%');
});
