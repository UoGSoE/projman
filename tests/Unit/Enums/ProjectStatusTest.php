<?php

use App\Enums\ProjectStatus;

it('provides a human-readable label for every case', function () {
    expect(ProjectStatus::IDEATION->label())->toBe('Ideation')
        ->and(ProjectStatus::FEASIBILITY->label())->toBe('Feasibility')
        ->and(ProjectStatus::SCOPING->label())->toBe('Scoping')
        ->and(ProjectStatus::SCHEDULING->label())->toBe('Scheduling')
        ->and(ProjectStatus::DETAILED_DESIGN->label())->toBe('Detailed Design')
        ->and(ProjectStatus::DEVELOPMENT->label())->toBe('Development')
        ->and(ProjectStatus::BUILD->label())->toBe('Build')
        ->and(ProjectStatus::TESTING->label())->toBe('Testing')
        ->and(ProjectStatus::DEPLOYED->label())->toBe('Deployed')
        ->and(ProjectStatus::COMPLETED->label())->toBe('Completed')
        ->and(ProjectStatus::CANCELLED->label())->toBe('Cancelled');
});

it('returns a Flux colour name for every case', function (ProjectStatus $status) {
    $fluxColours = ['zinc', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];

    expect($status->colour())->toBeIn($fluxColours);
})->with(ProjectStatus::cases());
