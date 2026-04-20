<?php

use App\Enums\Busyness;

it('exposes a colour helper for each case', function () {
    expect(Busyness::UNKNOWN->colour())->toBe('bg-gray-600')
        ->and(Busyness::LOW->colour())->toBe('bg-green-500')
        ->and(Busyness::MEDIUM->colour())->toBe('bg-yellow-500')
        ->and(Busyness::HIGH->colour())->toBe('bg-red-500');
});
