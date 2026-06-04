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

it('maps each case to its intended Flux colour', function () {
    expect(ProjectStatus::IDEATION->colour())->toBe('lime')
        ->and(ProjectStatus::FEASIBILITY->colour())->toBe('green')
        ->and(ProjectStatus::SCOPING->colour())->toBe('amber')
        ->and(ProjectStatus::SCHEDULING->colour())->toBe('amber')
        ->and(ProjectStatus::DETAILED_DESIGN->colour())->toBe('amber')
        ->and(ProjectStatus::DEVELOPMENT->colour())->toBe('amber')
        ->and(ProjectStatus::BUILD->colour())->toBe('amber')
        ->and(ProjectStatus::TESTING->colour())->toBe('amber')
        ->and(ProjectStatus::DEPLOYED->colour())->toBe('green')
        ->and(ProjectStatus::COMPLETED->colour())->toBe('zinc')
        ->and(ProjectStatus::CANCELLED->colour())->toBe('red');
});
