<?php

use App\Events\ServiceAcceptanceRequested;
use App\Events\UATRequested;
use App\Livewire\ProjectEditor;
use App\Mail\ServiceAcceptanceRequestedMail;
use App\Mail\UATRequestedMail;
use App\Models\Project;
use App\Models\Role;
use App\Models\Testing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// Helper to create a project in the Testing stage whose testing record is fully
// filled in (valid form data, all sign-offs pending).
//
// When events are live, the ProjectCreated listener has already created the real
// testing record, so we fill that one - hasTesting() would add an orphaned second
// row that $project->testing never returns. When events are faked the listener
// never ran, so updateOrCreate creates the record instead.
function createTestingProject(array $projectAttributes = []): Project
{
    $project = Project::factory()->create(array_merge(['status' => 'testing'], $projectAttributes));

    $project->testing()->updateOrCreate(
        ['project_id' => $project->id],
        Testing::factory()->complete()->raw(['project_id' => $project->id])
    );

    return $project->fresh();
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
            ->call('requestUAT')
            ->assertHasNoErrors();

        // Assert - one mail, carrying this project, to the assigned UAT Tester only
        Mail::assertQueued(UATRequestedMail::class, 1);
        Mail::assertQueued(UATRequestedMail::class, function ($mail) use ($uatTester, $project) {
            return $mail->hasTo($uatTester->email) && $mail->project->is($project);
        });
        Mail::assertNotQueued(UATRequestedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
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
        expect(str_contains($latestHistory->description, 'Requested UAT'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('saves department_office field correctly', function () {
        // Arrange - createTestingProject seeds a fully valid form, so only the
        // field under test needs setting
        $user = User::factory()->create(['is_admin' => true]);
        $project = createTestingProject();
        $this->actingAs($user);

        expect($project->testing->department_office)->toBeNull();

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.departmentOffice', 'IT Department')
            ->call('save', 'testing')
            ->assertHasNoErrors();

        // Assert - field persists after refresh
        $project = $project->fresh(['testing']);
        expect($project->testing->department_office)->toBe('IT Department');
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
        $owner = User::factory()->create();
        $project = createTestingProject(['user_id' => $owner->id]);
        $project->testing->update(['user_acceptance' => 'approved']);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('requestServiceAcceptance')
            ->assertHasNoErrors();

        // Assert - one mail, carrying this project, to the Service Lead
        Mail::assertQueued(ServiceAcceptanceRequestedMail::class, 1);
        Mail::assertQueued(ServiceAcceptanceRequestedMail::class, function ($mail) use ($serviceLead, $project) {
            return $mail->hasTo($serviceLead->email) && $mail->project->is($project);
        });

        // Assert - the project owner is not notified (config excludes the owner)
        Mail::assertNotQueued(ServiceAcceptanceRequestedMail::class, function ($mail) use ($owner) {
            return $mail->hasTo($owner->email);
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
        expect(str_contains($latestHistory->description, 'Requested Service Acceptance'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
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
            ->assertHasErrors('testingForm.testingLeadSignOff');

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
            ->assertHasErrors('testingForm.serviceDeliverySignOff');

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

        // Assert - submitTesting creates 2 history entries: "Submitted testing" + the stage change
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 2);

        // Check all history entries exist (order may vary due to timing)
        $historyEntries = $project->history()->latest()->take(2)->get();
        $descriptions = $historyEntries->pluck('description')->implode(' ');
        expect($descriptions)->toContain('Stage changed to deployed');
        expect($descriptions)->toContain('Submitted testing');

        // The "Submitted testing" entry is attributed to the acting user
        $submitted = $historyEntries->first(fn ($entry) => str_contains($entry->description, 'Submitted testing'));
        expect($submitted->user_id)->toBe($user->id);
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
        // Arrange - the UAT tester must be IT staff to pass the saveForm policy
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->staff()->create();
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

        // Step 2: UAT Tester approves (the form is already valid from the fixture,
        // so only the approval fields change)
        $this->actingAs($uatTester);
        livewire(ProjectEditor::class, ['project' => $project])
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
        // Arrange - the UAT tester must be IT staff to pass the saveForm policy
        $user = User::factory()->create(['is_admin' => true]);
        $uatTester = User::factory()->staff()->create();
        $project = createTestingProject();
        $this->actingAs($user);

        // Step 1: Request UAT
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('testingForm.uatTesterId', $uatTester->id)
            ->call('requestUAT')
            ->assertHasNoErrors();

        // Step 2: UAT Tester rejects (the form is already valid from the fixture,
        // so only the rejection fields change)
        $this->actingAs($uatTester);
        livewire(ProjectEditor::class, ['project' => $project])
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

        // Assert: the request was refused and the project stays in Testing
        $project->refresh();
        expect($project->testing->service_acceptance_requested_at)->toBeNull();
        expect($project->status->value)->toBe('testing');
    });
});
