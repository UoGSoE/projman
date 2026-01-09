<?php

use App\Enums\EffortScale;
use App\Enums\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeAllProjectEvents();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->staffUser = User::factory()->create(['is_admin' => false, 'is_staff' => true]);
});

test('admin can access project export', function () {
    $project = $this->createProject();

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertOk()
        ->assertSee($project->title);
});

test('non-admin cannot access project export', function () {
    $project = $this->createProject();

    $this->actingAs($this->staffUser)
        ->get(route('project.export', $project))
        ->assertForbidden();
});

test('guest cannot access project export', function () {
    $project = $this->createProject();

    $this->get(route('project.export', $project))
        ->assertRedirect(route('login'));
});

test('export displays project title and reference number', function () {
    $project = $this->createProject(['title' => 'Data Migration Project']);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Data Migration Project')
        ->assertSee("Work Package Reference #{$project->id}");
});

test('export displays university of glasgow header', function () {
    $project = $this->createProject();

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('University of Glasgow');
});

test('export displays owner information', function () {
    $owner = User::factory()->create([
        'forenames' => 'John',
        'surname' => 'Smith',
    ]);
    $project = $this->createProject(['user_id' => $owner->id]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('John Smith');
});

test('export displays project status', function () {
    $project = $this->createProject();

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee($project->status->label());
});

test('export includes ideation stage data', function () {
    $project = $this->createProject();
    $project->ideation->update([
        'objective' => 'Streamline data processing',
        'business_case' => 'Reduce manual entry by 50%',
        'benefits' => 'Improved efficiency',
        'strategic_initiative' => 'Digital transformation',
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Ideation')
        ->assertSee('Streamline data processing')
        ->assertSee('Reduce manual entry by 50%')
        ->assertSee('Improved efficiency')
        ->assertSee('Digital transformation');
});

test('export includes feasibility stage data', function () {
    $assessor = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Assessor']);
    $project = $this->createProject();
    $project->feasibility->update([
        'technical_credence' => 'High technical credence',
        'cost_benefit_case' => 'Strong ROI expected',
        'approval_status' => 'approved',
        'assessed_by' => $assessor->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Feasibility')
        ->assertSee('High technical credence')
        ->assertSee('Strong ROI expected')
        ->assertSee('Jane Assessor');
});

test('export includes scoping stage data', function () {
    $project = $this->createProject();
    $project->scoping->update([
        'estimated_effort' => EffortScale::LARGE,
        'in_scope' => 'Core features',
        'out_of_scope' => 'Legacy integration',
        'assumptions' => 'Standard infrastructure available',
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Scoping')
        ->assertSee('Large')
        ->assertSee('Core features')
        ->assertSee('Legacy integration');
});

test('export includes scheduling stage data with user relationships', function () {
    $technicalLead = User::factory()->create(['forenames' => 'Jane', 'surname' => 'Developer']);
    $assignedUser = User::factory()->create(['forenames' => 'Bob', 'surname' => 'Worker']);

    $project = $this->createProject();
    $project->scheduling->update([
        'technical_lead_id' => $technicalLead->id,
        'assigned_to' => $assignedUser->id,
        'priority' => Priority::PRIORITY_1->value,
        'estimated_start_date' => now()->addDays(7),
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Scheduling')
        ->assertSee('Jane Developer')
        ->assertSee('Bob Worker')
        ->assertSee('Priority 1')
        ->assertSee(now()->addDays(7)->format('d/m/Y'));
});

test('export conditionally shows development section when software dev required', function () {
    $leadDev = User::factory()->create(['forenames' => 'Alice', 'surname' => 'Coder']);
    $project = $this->createProject();
    $project->scoping->update(['requires_software_dev' => true]);
    $project->development->update([
        'lead_developer' => $leadDev->id,
        'technical_approach' => 'Agile methodology',
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Development')
        ->assertSee('Alice Coder')
        ->assertSee('Agile methodology');
});

test('export hides development section when software dev not required', function () {
    $project = $this->createProject();
    $project->scoping->update(['requires_software_dev' => false]);

    $response = $this->actingAs($this->admin)
        ->get(route('project.export', $project));

    $response->assertDontSee('>6. Development<');
});

test('export includes testing stage data', function () {
    $testLead = User::factory()->create(['forenames' => 'Test', 'surname' => 'Manager']);
    $project = $this->createProject();
    $project->testing->update([
        'test_lead' => $testLead->id,
        'functional_tests' => 'Unit and integration tests',
        'testing_sign_off' => 'approved',
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Testing')
        ->assertSee('Test Manager')
        ->assertSee('Unit and integration tests');
});

test('export includes deployed stage data', function () {
    $deploymentLead = User::factory()->create(['forenames' => 'Deploy', 'surname' => 'Lead']);
    $project = $this->createProject();
    $project->deployed->update([
        'deployment_lead_id' => $deploymentLead->id,
        'bau_operational_wiki' => 'https://wiki.example.com/ops',
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Deployed')
        ->assertSee('Deploy Lead')
        ->assertSee('https://wiki.example.com/ops');
});

test('export returns 404 for non-existent project', function () {
    $this->actingAs($this->admin)
        ->get(route('project.export', 99999))
        ->assertNotFound();
});

test('export handles missing optional data gracefully', function () {
    $project = $this->createProject();
    $project->ideation->update([
        'objective' => null,
        'business_case' => null,
    ]);

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertOk()
        ->assertSee('Not specified');
});

test('export displays footer with export date and app name', function () {
    $project = $this->createProject();

    $this->actingAs($this->admin)
        ->get(route('project.export', $project))
        ->assertSee('Exported on')
        ->assertSee(config('app.name'));
});

test('export button only visible to admins on project viewer', function () {
    $project = $this->createProject();

    $this->actingAs($this->admin)
        ->get(route('project.show', $project))
        ->assertSee('Export');

    $this->actingAs($this->staffUser)
        ->get(route('project.show', $project))
        ->assertDontSee('Export');
});
