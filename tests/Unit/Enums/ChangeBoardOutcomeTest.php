<?php

use App\Enums\ChangeBoardOutcome;

it('provides a human-readable label for every case', function () {
    expect(ChangeBoardOutcome::PENDING->label())->toBe('Pending')
        ->and(ChangeBoardOutcome::APPROVED->label())->toBe('Approved')
        ->and(ChangeBoardOutcome::DEFERRED->label())->toBe('Deferred')
        ->and(ChangeBoardOutcome::REJECTED->label())->toBe('Rejected')
        ->and(ChangeBoardOutcome::NOT_REQUIRED->label())->toBe('Not Required');
});
