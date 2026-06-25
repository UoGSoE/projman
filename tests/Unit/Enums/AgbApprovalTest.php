<?php

use App\Enums\AgbApproval;

it('provides a human-readable label for every case', function () {
    expect(AgbApproval::PENDING->label())->toBe('Pending')
        ->and(AgbApproval::APPROVED->label())->toBe('Approved')
        ->and(AgbApproval::REJECTED->label())->toBe('Rejected')
        ->and(AgbApproval::NOT_REQUIRED->label())->toBe('Not Required');
});
