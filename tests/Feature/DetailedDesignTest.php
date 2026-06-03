<?php

use App\Models\DetailedDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

it('persists an architecture governance board approval value', function () {
    $design = DetailedDesign::factory()->create(['approval_agb' => 'not_required']);

    expect($design->fresh()->approval_agb)->toBe('not_required');
});

it('produces an architecture governance board approval via the factory', function () {
    $design = DetailedDesign::factory()->create();

    expect($design->approval_agb)->not->toBeNull();
});

it('lowercases existing capitalised approval values when the migration runs', function () {
    $design = DetailedDesign::factory()->create();

    $design->update([
        'approval_delivery' => 'Approved',
        'approval_operations' => 'Pending',
        'approval_resilience' => 'Rejected',
        'approval_agb' => 'Not Required',
        'approval_change_board' => 'Not Required',
    ]);

    $updatedAt = $design->fresh()->updated_at;

    $migration = require glob(database_path('migrations/*_lowercase_detailed_design_approvals.php'))[0];
    $migration->up();

    expect($design->fresh())
        ->approval_delivery->toBe('approved')
        ->approval_operations->toBe('pending')
        ->approval_resilience->toBe('rejected')
        ->approval_agb->toBe('not_required')
        ->approval_change_board->toBe('not_required');

    // The tidy-up must not make every migrated row look freshly edited.
    expect($design->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});
