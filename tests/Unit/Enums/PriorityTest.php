<?php

use App\Enums\Priority;

it('exposes a human-readable label for each case', function () {
    expect(Priority::PRIORITY_1->label())->toBe('Priority 1')
        ->and(Priority::PRIORITY_2->label())->toBe('Priority 2')
        ->and(Priority::PRIORITY_3->label())->toBe('Priority 3')
        ->and(Priority::PRIORITY_4->label())->toBe('Priority 4')
        ->and(Priority::PRIORITY_5->label())->toBe('Priority 5');
});
