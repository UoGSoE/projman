<?php

use App\Enums\EffortScale;
use App\Livewire\ChangeOnAPage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setupBaseNotificationRoles();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('change on a page renders successfully', function () {
    $project = Project::factory()->create();

    $response = $this->get(route('portfolio.change-on-a-page', $project));

    $response->assertOk();
    $response->assertSeeLivewire(ChangeOnAPage::class);
});

test('displays project title and reference number', function () {
    $project = Project::factory()->create([
        'title' => 'Data Migration Project',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Data Migration Project')
        ->assertSee("Project Reference #{$project->id}");
});

test('displays champion and raised by information', function () {
    $owner = User::factory()->create([
        'forenames' => 'John',
        'surname' => 'Doe',
    ]);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
    ]);

    $project->ideation->update([
        'school_group' => 'Engineering Department',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Engineering Department')
        ->assertSee('John Doe');
});

test('displays objective and business case in teal section', function () {
    $project = Project::factory()->create();

    $project->ideation->update([
        'objective' => 'Streamline data processing workflows',
        'business_case' => 'Reduce manual data entry by 50%',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Streamline data processing workflows')
        ->assertSee('Reduce manual data entry by 50%');
});

test('displays benefits, in-scope, and out-of-scope in orange section', function () {
    $project = Project::factory()->create();

    $project->ideation->update([
        'benefits' => 'Faster processing and fewer errors',
    ]);

    $project->scoping->update([
        'in_scope' => 'Core data migration features',
        'out_of_scope' => 'Legacy system maintenance',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Faster processing and fewer errors')
        ->assertSee('Core data migration features')
        ->assertSee('Legacy system maintenance');
});

test('displays approved recommendation in purple section', function () {
    $project = Project::factory()->create();

    $project->feasibility->update([
        'approval_status' => 'approved',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Approved for progression to next stage');
});

test('displays rejected recommendation with reason', function () {
    $project = Project::factory()->create();

    $project->feasibility->update([
        'approval_status' => 'rejected',
        'reject_reason' => 'Budget constraints',
        'existing_solution_notes' => 'Use existing CRM system',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Project rejected')
        ->assertSee('Budget constraints')
        ->assertSee('Use existing CRM system');
});

test('displays rejected recommendation with off-the-shelf solution', function () {
    $project = Project::factory()->create();

    $project->feasibility->update([
        'approval_status' => 'rejected',
        'reject_reason' => 'Not cost effective',
        'off_the_shelf_solution_notes' => 'Consider Salesforce',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Project rejected')
        ->assertSee('Not cost effective')
        ->assertSee('Consider Salesforce');
});

test('displays pending recommendation when not yet assessed', function () {
    $project = Project::factory()->create();

    $project->feasibility->update([
        'approval_status' => 'pending',
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Pending feasibility assessment');
});

test('displays priority, effort, and start date in pink section', function () {
    $project = Project::factory()->create();

    $project->scheduling->update([
        'priority' => 'High',
        'estimated_start_date' => now()->addDays(10),
    ]);

    $project->scoping->update([
        'estimated_effort' => EffortScale::LARGE,
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('High')
        ->assertSee('Large')
        ->assertSee(now()->addDays(10)->format('d/m/Y'));
});

test('displays technical details section when technical owner is assigned', function () {
    $technicalOwner = User::factory()->create([
        'forenames' => 'Jane',
        'surname' => 'Smith',
    ]);

    $project = Project::factory()->create();

    $project->scheduling->update([
        'assigned_to' => $technicalOwner->id,
        'estimated_completion_date' => now()->addDays(60),
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Technical Details')
        ->assertSee('Jane Smith')
        ->assertSee(now()->addDays(60)->format('d/m/Y'));
});

test('hides technical details section when no technical information available', function () {
    $project = Project::factory()->create();

    $project->scheduling->update([
        'assigned_to' => null,
        'estimated_completion_date' => null,
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertDontSee('Technical Details');
});

test('displays back to backlog button', function () {
    $project = Project::factory()->create();

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Back to Backlog');
});

test('returns 404 for non-existent project', function () {
    $response = $this->get(route('portfolio.change-on-a-page', 99999));

    $response->assertNotFound();
});

test('handles missing optional data gracefully', function () {
    $project = Project::factory()->create();

    $project->ideation->update([
        'objective' => null,
        'business_case' => null,
        'benefits' => null,
        'school_group' => null,
    ]);

    $project->scoping->update([
        'in_scope' => null,
        'out_of_scope' => null,
        'estimated_effort' => null,
    ]);

    $project->scheduling->update([
        'priority' => null,
        'estimated_start_date' => null,
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        ->assertSee('Not specified')
        ->assertSee('Not set')
        ->assertSee('Not assessed')
        ->assertSee('Not scheduled');
});

test('shows all stage data correctly in comprehensive view', function () {
    $owner = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Johnson']);
    $technicalOwner = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Williams']);

    $project = Project::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Comprehensive Test Project',
    ]);

    // Ideation stage
    $project->ideation->update([
        'school_group' => 'IT Services',
        'objective' => 'Improve system performance',
        'business_case' => 'Reduce operational costs',
        'benefits' => 'Better user experience',
    ]);

    // Feasibility stage
    $project->feasibility->update([
        'approval_status' => 'approved',
    ]);

    // Scoping stage
    $project->scoping->update([
        'in_scope' => 'Performance optimization',
        'out_of_scope' => 'UI redesign',
        'estimated_effort' => EffortScale::X_LARGE,
    ]);

    // Scheduling stage
    $project->scheduling->update([
        'priority' => 'Critical',
        'assigned_to' => $technicalOwner->id,
        'estimated_start_date' => now()->addDays(7),
        'estimated_completion_date' => now()->addDays(45),
    ]);

    livewire(ChangeOnAPage::class, ['project' => $project])
        // Header
        ->assertSee('Comprehensive Test Project')
        ->assertSee("Project Reference #{$project->id}")
        // Row 1
        ->assertSee('IT Services')
        ->assertSee('Alice Johnson')
        // Teal section
        ->assertSee('Improve system performance')
        ->assertSee('Reduce operational costs')
        // Orange section
        ->assertSee('Better user experience')
        ->assertSee('Performance optimization')
        ->assertSee('UI redesign')
        // Purple section
        ->assertSee('Approved for progression to next stage')
        // Pink section
        ->assertSee('Critical')
        ->assertSee('X-Large')
        ->assertSee(now()->addDays(7)->format('d/m/Y'))
        // Gray section
        ->assertSee('Technical Details')
        ->assertSee('Bob Williams')
        ->assertSee(now()->addDays(45)->format('d/m/Y'));
});
