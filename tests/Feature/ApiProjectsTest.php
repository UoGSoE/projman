<?php

use App\Enums\ProjectStatus;
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

it('returns the full project shape with empty assignments when scheduling is unassigned', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $project = Project::factory()->create([
        'title' => 'Lone project',
        'status' => ProjectStatus::IDEATION,
        'school_group' => 'School of Computing',
    ]);

    $response = $this->withHeaders(projectBearerFor($admin))
        ->getJson('/api/projects')
        ->assertOk();

    $row = collect($response->json('data'))->firstWhere('id', $project->id);

    expect($row)->toHaveKeys(['id', 'title', 'status', 'school_group', 'assignments']);
    expect($row['title'])->toBe('Lone project');
    expect($row['status'])->toBe(ProjectStatus::IDEATION->value);
    expect($row['school_group'])->toBe('School of Computing');
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

it('returns paginated projects with pagination metadata', function () {
    $admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    Project::factory()->count(3)->create();

    $response = $this->withHeaders(projectBearerFor($admin))
        ->getJson('/api/projects')
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);

    expect($response->json('meta.total'))->toBe(3);
    expect($response->json('meta.current_page'))->toBe(1);
    expect($response->json('data'))->toHaveCount(3);
});

it('rejects unauthenticated requests to the projects endpoint', function () {
    $this->getJson('/api/projects')->assertUnauthorized();
});
