<?php

use App\Models\DetailedDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

it('persists an architecture governance board approval value', function () {
    $design = DetailedDesign::factory()->create(['approval_agb' => 'Not Required']);

    expect($design->fresh()->approval_agb)->toBe('Not Required');
});

it('produces an architecture governance board approval via the factory', function () {
    $design = DetailedDesign::factory()->create();

    expect($design->approval_agb)->not->toBeNull();
});
