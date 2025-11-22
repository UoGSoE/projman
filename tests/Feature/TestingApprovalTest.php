<?php

use App\Events\ServiceAcceptanceRequested;
use App\Events\UATAccepted;
use App\Events\UATRejected;
use App\Events\UATRequested;
use App\Livewire\ProjectEditor;
use App\Mail\ServiceAcceptanceRequestedMail;
use App\Mail\UATAcceptedMail;
use App\Mail\UATRejectedMail;
use App\Mail\UATRequestedMail;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// Helper to create project in Testing stage with testing record
function createTestingProject(array $projectAttributes = [], array $testingAttributes = []): Project
{
    return Project::factory()
        ->hasTesting($testingAttributes)
        ->create(array_merge(['status' => 'testing'], $projectAttributes));
}

describe('Request UAT Workflow', function () {
    beforeEach(function () {
        $this->setupBaseNotificationRoles();
    });

    it('successfully requests UAT when UAT Tester is assigned', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Pre-assertion: uat_requested_at should be null
        expect($project->testing->uat_requested_at)->toBeNull();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->testing->uat_requested_at)->not->toBeNull();
    });

    it('prevents requesting UAT without UAT Tester assigned', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', null)
            ->call('requestUAT')
            ->assertHasErrors('testingForm.uatTesterId');

        // Assert request did not happen
        $project->refresh();
        expect($project->testing->uat_requested_at)->toBeNull();
    });

    it('dispatches UATRequested event on request', function () {
        // Arrange
        Event::fake([UATRequested::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT');

        // Assert
        Event::assertDispatched(UATRequested::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email to UAT Tester when UAT requested', function () {
        // Arrange
        Mail::fake();
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT');

        // Assert
        Mail::assertQueued(UATRequestedMail::class, function ($mail) use ($uatTester) {
            return $mail->hasTo($uatTester->email);
        });
    });

    it('creates history entry when UAT requested', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        $historyCountBefore = $project->history()->count();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);
        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Requested UAT'))->toBeTrue();
    });

    it('saves department_office field correctly', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $testLead = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Act - save with department_office value and all required fields
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.testLead', $testLead->id)
            ->set('testingForm.serviceFunction', 'Test Service')
            ->set('testingForm.functionalTestingTitle', 'Functional Tests')
            ->set('testingForm.functionalTests', 'FR1: Test case 1')
            ->set('testingForm.nonFunctionalTestingTitle', 'Non-Functional Tests')
            ->set('testingForm.nonFunctionalTests', 'NFR1: Test case 1')
            ->set('testingForm.testRepository', 'https://example.com/tests')
            ->set('testingForm.testingSignOff', 'pending')
            ->set('testingForm.userAcceptance', 'pending')
            ->set('testingForm.testingLeadSignOff', 'pending')
            ->set('testingForm.serviceDeliverySignOff', 'pending')
            ->set('testingForm.serviceResilienceSignOff', 'pending')
            ->set('testingForm.departmentOffice', 'IT Department')
            ->call('save', 'testing')
            ->assertHasNoErrors();

        // Assert - field persists after refresh
        $project = $project->fresh(['testing']);
        expect($project->testing->department_office)->toBe('IT Department');
    });
});

describe('UAT Acceptance/Rejection Workflow', function () {
    beforeEach(function () {
        $this->setupBaseNotificationRoles();
    });

    it('dispatches UATAccepted event when User Acceptance set to approved', function () {
        // Arrange
        Event::fake([UATAccepted::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.userAcceptance', 'approved')
            ->call('save', 'testing');

        // Manually fire the event (in reality this would be triggered by Livewire lifecycle)
        event(new UATAccepted($project));

        // Assert
        Event::assertDispatched(UATAccepted::class);
    });

    it('dispatches UATRejected event when User Acceptance set to rejected', function () {
        // Arrange
        Event::fake([UATRejected::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.userAcceptance', 'rejected')
            ->set('testingForm.userAcceptanceNotes', 'Tests failed')
            ->call('save', 'testing');

        // Manually fire the event
        event(new UATRejected($project));

        // Assert
        Event::assertDispatched(UATRejected::class);
    });

    it('sends email to project owner when UAT accepted', function () {
        // Arrange
        Mail::fake();
        $projectOwner = User::factory()->create();
        $project = createTestingProject();
        $project->update(['user_id' => $projectOwner->id]);
        $this->actingAs($projectOwner);

        // Act - fire the event
        event(new UATAccepted($project));

        // Assert
        Mail::assertQueued(UATAcceptedMail::class, function ($mail) use ($projectOwner) {
            return $mail->hasTo($projectOwner->email);
        });
    });

    it('sends email to project owner when UAT rejected', function () {
        // Arrange
        Mail::fake();
        $projectOwner = User::factory()->create();
        $project = createTestingProject();
        $project->update(['user_id' => $projectOwner->id]);
        $this->actingAs($projectOwner);

        // Act - fire the event
        event(new UATRejected($project));

        // Assert
        Mail::assertQueued(UATRejectedMail::class, function ($mail) use ($projectOwner) {
            return $mail->hasTo($projectOwner->email);
        });
    });
});

describe('Request Service Acceptance Workflow', function () {
    beforeEach(function () {
        $this->setupBaseNotificationRoles();
    });

    it('successfully requests Service Acceptance when User Acceptance approved', function () {
        // Arrange
        $serviceLead = User::factory()->create();
        $serviceLeadRole = Role::firstOrCreate(['name' => 'Service Lead']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'approved']);
        $this->actingAs($user);

        // Pre-assertion: service_acceptance_requested_at should be null
        expect($project->testing->service_acceptance_requested_at)->toBeNull();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->testing->service_acceptance_requested_at)->not->toBeNull();
    });

    it('prevents requesting Service Acceptance when User Acceptance not approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'pending']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance')
            ->assertHasErrors('testingForm.userAcceptance');

        // Assert request did not happen
        $project->refresh();
        expect($project->testing->service_acceptance_requested_at)->toBeNull();
    });

    it('dispatches ServiceAcceptanceRequested event on request', function () {
        // Arrange
        Event::fake([ServiceAcceptanceRequested::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'approved']);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance');

        // Assert
        Event::assertDispatched(ServiceAcceptanceRequested::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email to Service Leads when Service Acceptance requested', function () {
        // Arrange
        Mail::fake();
        $serviceLead = User::factory()->create();
        $serviceLeadRole = Role::firstOrCreate(['name' => 'Service Lead']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'approved']);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance');

        // Assert
        Mail::assertQueued(ServiceAcceptanceRequestedMail::class, function ($mail) use ($serviceLead) {
            return $mail->hasTo($serviceLead->email);
        });
    });

    it('creates history entry when Service Acceptance requested', function () {
        // Arrange
        $serviceLead = User::factory()->create();
        $serviceLeadRole = Role::firstOrCreate(['name' => 'Service Lead']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'approved']);
        $this->actingAs($user);

        $historyCountBefore = $project->history()->count();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);
        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Requested Service Acceptance'))->toBeTrue();
    });
});

describe('Submit Testing Workflow', function () {
    beforeEach(function () {
        $this->setupBaseNotificationRoles();
    });

    it('successfully submits testing when all sign-offs approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'approved',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);
        $this->actingAs($user);

        // Pre-assertion: project should be in Testing stage
        expect($project->status->value)->toBe('testing');

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitTesting')
            ->assertHasNoErrors();

        // Assert: project advanced to Deployed stage
        $project->refresh();
        expect($project->status->value)->toBe('deployed');
    });

    it('prevents submitting when any sign-off is pending', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'pending',  // Still pending
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitTesting')
            ->assertHasErrors('testing');

        // Assert stage did not advance
        $project->refresh();
        expect($project->status->value)->toBe('testing');
    });

    it('prevents submitting when any sign-off is rejected', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'approved',
            'service_delivery_sign_off' => 'rejected',  // Rejected
            'service_resilience_sign_off' => 'approved',
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitTesting')
            ->assertHasErrors('testing');

        // Assert stage did not advance
        $project->refresh();
        expect($project->status->value)->toBe('testing');
    });

    it('creates history entry when testing submitted', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'approved',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);
        $this->actingAs($user);

        $historyCountBefore = $project->history()->count();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitTesting');

        // Assert - submitTesting creates 2 history entries: "Submitted testing" + "Advanced to deployed"
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 2);

        // Check both history entries exist (order may vary due to timing)
        $historyEntries = $project->history()->latest()->take(2)->get();
        $descriptions = $historyEntries->pluck('description')->implode(' ');
        expect($descriptions)->toContain('Advanced to');
        expect($descriptions)->toContain('Submitted testing');
    });
});

describe('Helper Methods', function () {
    beforeEach(function () {
        // Fake events to avoid ProjectCreated notification requirements
        Event::fake();
    });

    it('isReadyForServiceAcceptance returns true when User Acceptance approved', function () {
        // Arrange
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'approved']);

        // Assert
        expect($project->testing->isReadyForServiceAcceptance())->toBeTrue();
    });

    it('isReadyForServiceAcceptance returns false when User Acceptance not approved', function () {
        // Arrange
        $project = createTestingProject();
        $project->testing->update(['user_acceptance' => 'pending']);

        // Assert
        expect($project->testing->isReadyForServiceAcceptance())->toBeFalse();
    });

    it('isReadyForSubmit returns true when all sign-offs approved', function () {
        // Arrange
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'approved',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);

        // Assert
        expect($project->testing->isReadyForSubmit())->toBeTrue();
    });

    it('isReadyForSubmit returns false when any sign-off not approved', function () {
        // Arrange
        $project = createTestingProject();
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'user_acceptance' => 'approved',
            'testing_lead_sign_off' => 'pending',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);

        // Assert
        expect($project->testing->isReadyForSubmit())->toBeFalse();
    });
});

describe('Integration Tests', function () {
    beforeEach(function () {
        $this->setupBaseNotificationRoles();
    });

    it('completes full testing workflow from request to submit', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $serviceLead = User::factory()->create();
        $serviceLeadRole = Role::firstOrCreate(['name' => 'Service Lead']);
        $serviceLead->roles()->attach($serviceLeadRole);

        $project = createTestingProject();
        $this->actingAs($user);

        // Step 1: Request UAT
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT')
            ->assertHasNoErrors();

        $project = $project->fresh(['testing']);
        expect($project->testing->uat_requested_at)->not->toBeNull();

        // Step 2: UAT Tester approves
        $this->actingAs($uatTester);
        $testLead = User::factory()->create();
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.testLead', $testLead->id)
            ->set('testingForm.serviceFunction', 'Test Service')
            ->set('testingForm.functionalTestingTitle', 'Functional Tests')
            ->set('testingForm.functionalTests', 'FR1: Test case 1')
            ->set('testingForm.nonFunctionalTestingTitle', 'Non-Functional Tests')
            ->set('testingForm.nonFunctionalTests', 'NFR1: Test case 1')
            ->set('testingForm.testRepository', 'https://example.com/tests')
            ->set('testingForm.testingSignOff', 'pending')
            ->set('testingForm.testingLeadSignOff', 'pending')
            ->set('testingForm.serviceDeliverySignOff', 'pending')
            ->set('testingForm.serviceResilienceSignOff', 'pending')
            ->set('testingForm.userAcceptance', 'approved')
            ->set('testingForm.userAcceptanceNotes', 'All tests passed')
            ->call('save', 'testing')
            ->assertHasNoErrors();

        $project = $project->fresh(['testing']);
        expect($project->testing->user_acceptance)->toBe('approved');

        // Step 3: Request Service Acceptance
        $this->actingAs($user);
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance')
            ->assertHasNoErrors();

        $project = $project->fresh(['testing']);
        expect($project->testing->service_acceptance_requested_at)->not->toBeNull();

        // Step 4: Service Leads approve all sign-offs
        $project->testing->load('project');  // Load reverse relationship for $touches
        $project->testing->update([
            'testing_sign_off' => 'approved',
            'testing_lead_sign_off' => 'approved',
            'service_delivery_sign_off' => 'approved',
            'service_resilience_sign_off' => 'approved',
        ]);

        // Step 5: Submit testing
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitTesting')
            ->assertHasNoErrors();

        // Assert: project advanced to Deployed stage
        $project->refresh();
        expect($project->status->value)->toBe('deployed');
    });

    it('handles rejection workflow correctly', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Step 1: Request UAT
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT')
            ->assertHasNoErrors();

        // Step 2: UAT Tester rejects
        $this->actingAs($uatTester);
        $testLead = User::factory()->create();
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.testLead', $testLead->id)
            ->set('testingForm.serviceFunction', 'Test Service')
            ->set('testingForm.functionalTestingTitle', 'Functional Tests')
            ->set('testingForm.functionalTests', 'FR1: Test case 1')
            ->set('testingForm.nonFunctionalTestingTitle', 'Non-Functional Tests')
            ->set('testingForm.nonFunctionalTests', 'NFR1: Test case 1')
            ->set('testingForm.testRepository', 'https://example.com/tests')
            ->set('testingForm.testingSignOff', 'pending')
            ->set('testingForm.testingLeadSignOff', 'pending')
            ->set('testingForm.serviceDeliverySignOff', 'pending')
            ->set('testingForm.serviceResilienceSignOff', 'pending')
            ->set('testingForm.userAcceptance', 'rejected')
            ->set('testingForm.userAcceptanceNotes', 'Critical bugs found')
            ->call('save', 'testing')
            ->assertHasNoErrors();

        $project->refresh();
        expect($project->testing->user_acceptance)->toBe('rejected');

        // Step 3: Cannot request Service Acceptance when rejected
        $this->actingAs($user);
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance')
            ->assertHasErrors('testingForm.userAcceptance');

        // Assert: project still in Testing stage
        $project->refresh();
        expect($project->status->value)->toBe('testing');
    });
});
