<?php

use App\Support\HeatmapCell;

it('maps utilisation values to the stakeholder colour bands', function (float $util, string $colour) {
    expect((new HeatmapCell($util))->colour())->toBe($colour);
})->with([
    'green at 0%' => [0.0, 'bg-green-500'],
    'green just under amber boundary' => [0.69, 'bg-green-500'],
    'amber at 70%' => [0.70, 'bg-amber-500'],
    'amber just under red boundary' => [0.89, 'bg-amber-500'],
    'red at 90%' => [0.90, 'bg-red-500'],
    'red at 100%' => [1.00, 'bg-red-500'],
    'black just over red' => [1.01, 'bg-black'],
    'black at 167%' => [1.67, 'bg-black'],
]);

it('renders a percentage label rounded to a whole number', function () {
    expect((new HeatmapCell(0.0))->label())->toBe('0%')
        ->and((new HeatmapCell(0.56))->label())->toBe('56%')
        ->and((new HeatmapCell(0.833))->label())->toBe('83%')
        ->and((new HeatmapCell(1.67))->label())->toBe('167%');
});
