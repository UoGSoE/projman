<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

function projectBearerFor(User $user): array
{
    $token = $user->createToken('Test')->plainTextToken;

    return ['Authorization' => "Bearer {$token}"];
}

it('lists projects with null assignments when no scheduling stage exists', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $project = Project::factory()->create(['title' => 'Lone project']);

    $response = $this->withHeaders(projectBearerFor($admin))
        ->getJson('/api/projects')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $project->id);

    expect($row['title'])->toBe('Lone project');
    expect($row['assignments']['assigned_to'])->toBeNull();
    expect($row['assignments']['technical_lead'])->toBeNull();
    expect($row['assignments']['change_champion'])->toBeNull();
    expect($row['assignments']['cose_it_staff'])->toBe([]);
});

it('populates assignment fields from the scheduling stage', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $project = Project::factory()->create(['title' => 'Staffed project']);

    $assignee = User::factory()->create(['forenames' => 'Ada', 'surname' => 'Lovelace']);
    $lead = User::factory()->create(['forenames' => 'Grace', 'surname' => 'Hopper']);
    $champion = User::factory()->create(['forenames' => 'Linus', 'surname' => 'Torvalds']);
    $cose1 = User::factory()->create(['forenames' => 'Alan', 'surname' => 'Turing']);
    $cose2 = User::factory()->create(['forenames' => 'Barbara', 'surname' => 'Liskov']);

    $project->scheduling->update([
        'assigned_to' => $assignee->id,
        'technical_lead_id' => $lead->id,
        'change_champion_id' => $champion->id,
        'cose_it_staff' => [$cose1->id, $cose2->id],
    ]);

    $response = $this->withHeaders(projectBearerFor($admin))
        ->getJson('/api/projects')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $project->id);

    expect($row['assignments']['assigned_to'])->toBe(['id' => $assignee->id, 'name' => 'Ada Lovelace']);
    expect($row['assignments']['technical_lead'])->toBe(['id' => $lead->id, 'name' => 'Grace Hopper']);
    expect($row['assignments']['change_champion'])->toBe(['id' => $champion->id, 'name' => 'Linus Torvalds']);
    expect($row['assignments']['cose_it_staff'])->toBe([
        ['id' => $cose1->id, 'name' => 'Alan Turing'],
        ['id' => $cose2->id, 'name' => 'Barbara Liskov'],
    ]);
});

it('rejects unauthenticated requests to the projects endpoint', function () {
    $this->getJson('/api/projects')->assertUnauthorized();
});
