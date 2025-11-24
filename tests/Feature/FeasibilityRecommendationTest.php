<?php

use App\Models\Feasibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

test('approved status returns approval recommendation', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'approved',
    ]);

    expect($feasibility->recommendation)
        ->toBe('Approved for progression to next stage');
});

test('pending status returns pending recommendation', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'pending',
    ]);

    expect($feasibility->recommendation)
        ->toBe('Pending feasibility assessment');
});

test('null status returns pending recommendation', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => null,
    ]);

    expect($feasibility->recommendation)
        ->toBe('Pending feasibility assessment');
});

test('rejected with only reject reason shows basic rejection', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'rejected',
        'reject_reason' => 'Project scope too large',
        'existing_solution_notes' => null,
        'off_the_shelf_solution_notes' => null,
    ]);

    expect($feasibility->recommendation)
        ->toBe('Project rejected. Reason: Project scope too large');
});

test('rejected with existing solution shows both reason and existing solution', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'rejected',
        'reject_reason' => 'Duplicate functionality',
        'existing_solution_notes' => 'Use the current CRM system',
        'off_the_shelf_solution_notes' => null,
    ]);

    expect($feasibility->recommendation)
        ->toBe('Project rejected. Reason: Duplicate functionality. Existing solution: Use the current CRM system');
});

test('rejected with off-the-shelf solution shows both reason and off-the-shelf solution', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'rejected',
        'reject_reason' => 'Better to buy than build',
        'existing_solution_notes' => null,
        'off_the_shelf_solution_notes' => 'Consider purchasing Salesforce',
    ]);

    expect($feasibility->recommendation)
        ->toBe('Project rejected. Reason: Better to buy than build. Off-the-shelf solution: Consider purchasing Salesforce');
});

test('rejected with all fields shows complete rejection message', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'rejected',
        'reject_reason' => 'Multiple alternatives available',
        'existing_solution_notes' => 'Internal tool already exists',
        'off_the_shelf_solution_notes' => 'Or purchase from vendor',
    ]);

    expect($feasibility->recommendation)
        ->toBe('Project rejected. Reason: Multiple alternatives available. Existing solution: Internal tool already exists. Off-the-shelf solution: Or purchase from vendor');
});

test('rejected without reason shows only rejection', function () {
    $feasibility = Feasibility::factory()->make([
        'approval_status' => 'rejected',
        'reject_reason' => null,
        'existing_solution_notes' => null,
        'off_the_shelf_solution_notes' => null,
    ]);

    expect($feasibility->recommendation)
        ->toBe('Project rejected');
});
