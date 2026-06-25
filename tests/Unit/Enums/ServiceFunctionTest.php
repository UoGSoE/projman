<?php

use App\Enums\ServiceFunction;

it('exposes a human-readable label for each case', function () {
    expect(ServiceFunction::COLLEGE_INFRASTRUCTURE->label())->toBe('College Infrastructure')
        ->and(ServiceFunction::RESEARCH_COMPUTING->label())->toBe('Research Computing')
        ->and(ServiceFunction::APPLICATIONS_DATA->label())->toBe('Applications & Data')
        ->and(ServiceFunction::SERVICE_RESILIENCE->label())->toBe('Service Resilience')
        ->and(ServiceFunction::SERVICE_DELIVERY->label())->toBe('Service Delivery');
});
