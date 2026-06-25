<?php

use App\Enums\ApprovalStatus;

it('provides a human-readable label for every case', function () {
    expect(ApprovalStatus::PENDING->label())->toBe('Pending')
        ->and(ApprovalStatus::APPROVED->label())->toBe('Approved')
        ->and(ApprovalStatus::REJECTED->label())->toBe('Rejected');
});
