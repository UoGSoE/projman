<?php

use App\Enums\ProjectStatus;
use App\Events\DeploymentApproved;
use App\Events\DeploymentServiceAccepted;
use App\Livewire\ProjectEditor;
use App\Mail\DeploymentApprovedMail;
use App\Mail\DeploymentServiceAcceptedMail;
use App\Models\Deployed;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// Global beforeEach to setup roles for all tests
beforeEach(function () {
    $this->setupBaseNotificationRoles();
});

// Helper to create project in Deployed stage with basic deployed record
// For tests needing specific states, use: Project::factory()->has(Deployed::factory()->state())->create()
function createDeployedProject(array $projectAttributes = []): Project
{
    return Project::factory()
        ->hasDeployed()
        ->create(array_merge(['status' => 'deployed'], $projectAttributes));
}

describe('Service Acceptance Workflow', function () {

    it('successfully accepts deployment service when all required fields are filled', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForServiceAcceptance()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Pre-assertion: service_accepted_at should be null
        expect($project->deployed->service_accepted_at)->toBeNull();
        expect($project->deployed->isReadyForServiceAcceptance())->toBeTrue();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->deployed->service_accepted_at)->not->toBeNull();
        expect($project->deployed->hasServiceAcceptance())->toBeTrue();
    });

    it('prevents service acceptance when required fields are incomplete', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->incomplete()->make()->toArray(), 'project_id')
        );
        $this->actingAs($user);

        // Pre-assertion
        expect($project->deployed->isReadyForServiceAcceptance())->toBeFalse();

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService')
            ->assertHasErrors('deployed');

        // Assert service acceptance did not happen
        $project->refresh();
        expect($project->deployed->service_accepted_at)->toBeNull();
    });

    it('dispatches DeploymentServiceAccepted event on acceptance', function () {
        // Arrange
        Event::fake([DeploymentServiceAccepted::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForServiceAcceptance()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService');

        // Assert
        Event::assertDispatched(DeploymentServiceAccepted::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email exactly once to Service Leads on service acceptance', function () {
        // Arrange
        Mail::fake();
        $serviceLeadRole = Role::where('name', 'Service Lead')->first();
        $serviceLead1 = User::factory()->create(['email' => 'lead1@example.com']);
        $serviceLead2 = User::factory()->create(['email' => 'lead2@example.com']);
        $serviceLead1->roles()->attach($serviceLeadRole);
        $serviceLead2->roles()->attach($serviceLeadRole);

        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForServiceAcceptance()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService');

        // Assert - sent exactly once with 3 recipients
        // (2 test Service Leads + 1 default Service Lead created in beforeEach)
        Mail::assertQueued(DeploymentServiceAcceptedMail::class, 1);
        Mail::assertQueued(DeploymentServiceAcceptedMail::class, function ($mail) use ($serviceLead1, $serviceLead2) {
            return $mail->hasTo($serviceLead1->email)
                && $mail->hasTo($serviceLead2->email)
                && count($mail->to) === 3;
        });
    });

    it('records timestamp when service acceptance is submitted', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()->readyForServiceAcceptance()->make()->toArray(),
                ['project_id', 'created_at', 'updated_at']
            )
        );

        $this->actingAs($user);

        $beforeDate = now()->format('Y-m-d H:i:s');

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService');

        $afterDate = now()->format('Y-m-d H:i:s');

        // Assert
        $project->refresh();
        $acceptedDate = $project->deployed->service_accepted_at->format('Y-m-d H:i:s');
        expect($project->deployed->service_accepted_at)->not->toBeNull();
        expect($acceptedDate)->toBeGreaterThanOrEqual($beforeDate);
        expect($acceptedDate)->toBeLessThanOrEqual($afterDate);
    });
});

describe('Deployment Approval Workflow', function () {
    it('successfully approves deployment when all service handover approvals are received', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForApproval()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Pre-assertion
        expect($project->deployed->isReadyForApproval())->toBeTrue();
        expect($project->deployed->deployment_approved_at)->toBeNull();
        expect($project->status)->toBe(ProjectStatus::DEPLOYED);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->deployed->deployment_approved_at)->not->toBeNull();
        expect($project->deployed->hasDeploymentApproval())->toBeTrue();
        expect($project->status)->toBe(ProjectStatus::COMPLETED);
    });

    it('prevents approval when service handover approvals are not all approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()
                    ->serviceAccepted()
                    ->state(fn (array $attributes) => [
                        'service_resilience_approval' => 'approved',
                        'service_operations_approval' => 'pending',  // Not approved
                        'service_delivery_approval' => 'approved',
                    ])
                    ->make()
                    ->toArray(),
                'project_id'
            )
        );

        $this->actingAs($user);

        // Pre-assertion
        expect($project->deployed->isReadyForApproval())->toBeFalse();

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment')
            ->assertHasErrors('deployed');

        // Assert approval did not happen
        $project->refresh();
        expect($project->deployed->deployment_approved_at)->toBeNull();
        expect($project->status)->toBe(ProjectStatus::DEPLOYED);
    });

    it('dispatches DeploymentApproved event on approval', function () {
        // Arrange
        Event::fake([DeploymentApproved::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForApproval()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment');

        // Assert
        Event::assertDispatched(DeploymentApproved::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email exactly once to Service Leads and project owner on approval', function () {
        // Arrange
        Mail::fake();
        $serviceLeadRole = Role::where('name', 'Service Lead')->first();
        $serviceLead = User::factory()->create(['email' => 'lead@example.com']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $projectOwner = User::factory()->create(['email' => 'owner@example.com']);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create(['user_id' => $projectOwner->id]);
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForApproval()->make()->toArray(), 'project_id')
        );
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment');

        // Assert - sent exactly once with 3 recipients
        // (1 test Service Lead + 1 project owner + 1 default Service Lead created in beforeEach)
        Mail::assertQueued(DeploymentApprovedMail::class, 1);
        Mail::assertQueued(DeploymentApprovedMail::class, function ($mail) use ($serviceLead, $projectOwner) {
            return $mail->hasTo($serviceLead->email)
                && $mail->hasTo($projectOwner->email)
                && count($mail->to) === 3;
        });
    });

    it('sets project status to COMPLETED on approval', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForApproval()->make()->toArray(), 'project_id')
        );
        $this->actingAs($user);

        // Pre-assertion
        expect($project->status)->toBe(ProjectStatus::DEPLOYED);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment');

        // Assert
        $project->refresh();
        expect($project->status)->toBe(ProjectStatus::COMPLETED);
    });

    it('records timestamp when deployment is approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForApproval()->make()->toArray(), 'project_id')
        );
        $this->actingAs($user);

        $beforeDate = now()->format('Y-m-d H:i:s');

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment');

        $afterDate = now()->format('Y-m-d H:i:s');

        // Assert
        $project->refresh();
        $approvedDate = $project->deployed->deployment_approved_at->format('Y-m-d H:i:s');
        expect($project->deployed->deployment_approved_at)->not->toBeNull();
        expect($approvedDate)->toBeGreaterThanOrEqual($beforeDate);
        expect($approvedDate)->toBeLessThanOrEqual($afterDate);
    });
});

describe('Helper Methods', function () {
    it('isReadyForServiceAcceptance returns true when all required fields are filled', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForServiceAcceptance()->make()->toArray(), 'project_id')
        );

        expect($project->deployed->isReadyForServiceAcceptance())->toBeTrue();
    });

    it('isReadyForServiceAcceptance returns false when required fields are missing', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->incomplete()->make()->toArray(), 'project_id')
        );

        expect($project->deployed->isReadyForServiceAcceptance())->toBeFalse();
    });

    it('isReadyForApproval returns true when all approvals are received', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()
                    ->state(fn (array $attributes) => [
                        'service_resilience_approval' => 'approved',
                        'service_operations_approval' => 'approved',
                        'service_delivery_approval' => 'approved',
                    ])
                    ->make()
                    ->toArray(),
                'project_id'
            )
        );

        expect($project->deployed->isReadyForApproval())->toBeTrue();
    });

    it('isReadyForApproval returns false when approvals are not all approved', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()
                    ->state(fn (array $attributes) => [
                        'service_resilience_approval' => 'approved',
                        'service_operations_approval' => 'pending',  // Not approved
                        'service_delivery_approval' => 'approved',
                    ])
                    ->make()
                    ->toArray(),
                'project_id'
            )
        );

        expect($project->deployed->isReadyForApproval())->toBeFalse();
    });

    it('hasServiceAcceptance returns true when service_accepted_at is set', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->serviceAccepted()->make()->toArray(), 'project_id')
        );

        expect($project->deployed->hasServiceAcceptance())->toBeTrue();
    });

    it('hasServiceAcceptance returns false when service_accepted_at is null', function () {
        $project = createDeployedProject();

        expect($project->deployed->hasServiceAcceptance())->toBeFalse();
    });

    it('needsServiceAcceptance returns true when service_accepted_at is null', function () {
        $project = createDeployedProject();

        expect($project->deployed->needsServiceAcceptance())->toBeTrue();
    });

    it('needsServiceAcceptance returns false when service_accepted_at is set', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(Deployed::factory()->serviceAccepted()->make()->toArray(), 'project_id')
        );

        expect($project->deployed->needsServiceAcceptance())->toBeFalse();
    });

    it('hasDeploymentApproval returns true when deployment_approved_at is set', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()
                    ->state(fn (array $attributes) => ['deployment_approved_at' => now()])
                    ->make()
                    ->toArray(),
                'project_id'
            )
        );

        expect($project->deployed->hasDeploymentApproval())->toBeTrue();
    });

    it('hasDeploymentApproval returns false when deployment_approved_at is null', function () {
        $project = createDeployedProject();

        expect($project->deployed->hasDeploymentApproval())->toBeFalse();
    });

    it('needsDeploymentApproval returns true when deployment_approved_at is null', function () {
        $project = createDeployedProject();

        expect($project->deployed->needsDeploymentApproval())->toBeTrue();
    });

    it('needsDeploymentApproval returns false when deployment_approved_at is set', function () {
        $project = Project::factory()->deployed()->create();
        $project->deployed->update(
            Arr::except(
                Deployed::factory()
                    ->state(fn (array $attributes) => ['deployment_approved_at' => now()])
                    ->make()
                    ->toArray(),
                'project_id'
            )
        );

        expect($project->deployed->needsDeploymentApproval())->toBeFalse();
    });
});

describe('Integration Tests', function () {
    it('completes full deployment workflow from service acceptance to approval', function () {
        // Arrange
        Mail::fake();
        $serviceLeadRole = Role::where('name', 'Service Lead')->first();
        $serviceLead = User::factory()->create(['email' => 'lead@example.com']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $projectOwner = User::factory()->create(['email' => 'owner@example.com']);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->deployed()->create(['user_id' => $projectOwner->id]);
        $project->deployed->update(
            Arr::except(Deployed::factory()->readyForServiceAcceptance()->make()->toArray(), 'project_id')
        );

        $this->actingAs($user);

        // Step 1: Service Acceptance
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('acceptDeploymentService')
            ->assertHasNoErrors();

        $project->refresh();
        expect($project->deployed->hasServiceAcceptance())->toBeTrue();
        expect($project->status)->toBe(ProjectStatus::DEPLOYED);

        // Assert first email sent
        Mail::assertQueued(DeploymentServiceAcceptedMail::class, 1);

        // Step 2: Approve all service handovers (eager load to prevent lazy loading)
        $project->load('deployed');
        $project->deployed->update([
            'service_resilience_approval' => 'approved',
            'service_operations_approval' => 'approved',
            'service_delivery_approval' => 'approved',
        ]);

        // Step 3: Final Approval
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveDeployment')
            ->assertHasNoErrors();

        // Assert final state
        $project->refresh();
        expect($project->deployed->hasDeploymentApproval())->toBeTrue();
        expect($project->status)->toBe(ProjectStatus::COMPLETED);

        // Assert both emails were sent (total of 2)
        Mail::assertQueued(DeploymentServiceAcceptedMail::class, 1);
        Mail::assertQueued(DeploymentApprovedMail::class, 1);
    });
});
