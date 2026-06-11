<?php

use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Livewire\ProjectEditor;
use App\Mail\FeasibilityApprovedMail;
use App\Mail\FeasibilityRejectedMail;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

/**
 * Put a feasibility into the saved, ready-for-approval state the real UI requires
 * before the approve/reject buttons appear: assessment complete and a solution
 * assessment recorded. Mirrors the gating tests further down this file.
 */
function completeSavedFeasibility(Project $project, User $assessor): void
{
    $project->feasibility->update([
        'assessed_by' => $assessor->id,
        'date_assessed' => now()->addDay(),
        'technical_credence' => 'Technically sound',
        'cost_benefit_case' => 'Good ROI',
        'dependencies_prerequisites' => 'None',
        'alternative_proposal' => 'No alternatives',
        'existing_solution_status' => 'no',
    ]);
}

describe('Feasibility Approval Workflow', function () {
    beforeEach(function () {
        // Set up notification roles required for ProjectCreated and Feasibility events
        $this->setupBaseNotificationRoles();
    });

    it('approves feasibility when no existing solution exists', function () {
        // Arrange - a saved, ready-for-approval feasibility (as the real UI requires)
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility')
            ->assertHasNoErrors();

        // Assert - approval recorded, and the saved solution assessment is left intact
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('approved')
            ->and($project->feasibility->approved_at)->not->toBeNull()
            ->and($project->feasibility->actioned_by)->toBe($user->id)
            ->and($project->feasibility->existing_solution_status)->toBe('no');
    });

    it('prevents approval when existing UoG solution is identified', function () {
        // Arrange - otherwise ready for approval, so only the 'yes' rule can block
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $this->actingAs($user);

        $project->feasibility->update([
            'existing_solution_status' => 'yes',
            'existing_solution_notes' => 'We already have System X',
        ]);

        // Act - try to call approve (should not work since button would be disabled in UI)
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility')
            ->assertHasErrors('feasibilityForm.existingSolutionStatus');

        // Assert approval did not happen
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('pending')
            ->and($project->feasibility->approved_at)->toBeNull();
    });

    it('prevents approval when off-the-shelf solution is identified', function () {
        // Arrange - otherwise ready for approval, so only the 'yes' rule can block
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $this->actingAs($user);

        $project->feasibility->update([
            'off_the_shelf_solution_status' => 'yes',
            'off_the_shelf_solution_notes' => 'Product XYZ is available',
        ]);

        // Act - try to call approve (should not work since button would be disabled in UI)
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility')
            ->assertHasErrors('feasibilityForm.offTheShelfSolutionStatus');

        // Assert approval did not happen
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('pending')
            ->and($project->feasibility->approved_at)->toBeNull();
    });

    it('requires reject reason when rejecting', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.rejectReason', null)
            ->call('rejectFeasibility')
            ->assertHasErrors('feasibilityForm.rejectReason');

        // Assert rejection did not happen
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('pending');
    });

    it('successfully rejects feasibility with valid reason', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $rejectReason = 'Existing solution meets requirements';
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.rejectReason', $rejectReason)
            ->call('rejectFeasibility')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('rejected')
            ->and($project->feasibility->reject_reason)->toBe($rejectReason)
            ->and($project->feasibility->actioned_by)->toBe($user->id);
    });

    it('dispatches FeasibilityApproved event on approval', function () {
        // Arrange - a saved, ready-for-approval feasibility (as the real UI requires)
        Event::fake([FeasibilityApproved::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility');

        // Assert
        Event::assertDispatched(FeasibilityApproved::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('dispatches FeasibilityRejected event on rejection', function () {
        // Arrange
        Event::fake([FeasibilityRejected::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.rejectReason', 'Not feasible')
            ->call('rejectFeasibility');

        // Assert
        Event::assertDispatched(FeasibilityRejected::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email to Work Package Assessor role on approval', function () {
        // Arrange
        Mail::fake();
        $role = Role::firstOrCreate(['name' => 'Work Package Assessor']);
        $assessor = User::factory()->create();
        $assessor->roles()->attach($role);

        $user = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);
        completeSavedFeasibility($project, $assessor);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility')
            ->assertHasNoErrors();

        // Assert - one mail, carrying this project, to the Work Package Assessor
        Mail::assertQueued(FeasibilityApprovedMail::class, 1);
        Mail::assertQueued(FeasibilityApprovedMail::class, function ($mail) use ($assessor, $project) {
            return $mail->hasTo($assessor->email) && $mail->project->is($project);
        });

        // Assert - the project owner is not notified on approval (config excludes the owner)
        Mail::assertNotQueued(FeasibilityApprovedMail::class, function ($mail) use ($owner) {
            return $mail->hasTo($owner->email);
        });
    });

    it('sends email to project owner on rejection', function () {
        // Arrange
        Mail::fake();
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.rejectReason', 'Not feasible at this time')
            ->call('rejectFeasibility')
            ->assertHasNoErrors();

        // Assert - one mail, carrying this project, to the project owner
        Mail::assertQueued(FeasibilityRejectedMail::class, 1);
        Mail::assertQueued(FeasibilityRejectedMail::class, function ($mail) use ($owner, $project) {
            return $mail->hasTo($owner->email) && $mail->project->is($project);
        });

        // Assert - the admin who actioned the rejection is not a recipient
        Mail::assertNotQueued(FeasibilityRejectedMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    });

    it('records history when feasibility is approved', function () {
        // Arrange - saved, ready-for-approval feasibility
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Approved feasibility'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('records history when feasibility is rejected', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.rejectReason', 'Not viable')
            ->call('rejectFeasibility');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Rejected feasibility'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('only affects the specific project when approving', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $projectToApprove = Project::factory()->create();
        $otherProject = Project::factory()->create();
        completeSavedFeasibility($projectToApprove, $assessor);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $projectToApprove])
            ->call('approveFeasibility');

        // Assert
        $projectToApprove->refresh();
        $otherProject->refresh();

        expect($projectToApprove->feasibility->approval_status)->toBe('approved')
            ->and($otherProject->feasibility->approval_status)->toBe('pending');
    });

    it('allows viewing approval status badge when approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->feasibility->update(['approval_status' => 'approved']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Approved');
    });

    it('allows viewing approval status badge when rejected', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->feasibility->update(['approval_status' => 'rejected']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Rejected');
    });

    it('persists new feasibility fields when saving', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.technicalCredence', 'Technically feasible')
            ->set('feasibilityForm.costBenefitCase', 'Cost effective solution')
            ->set('feasibilityForm.dependenciesPrerequisites', 'None')
            ->set('feasibilityForm.alternativeProposal', 'No alternatives')
            ->set('feasibilityForm.assessedBy', $assessor->id)
            ->set('feasibilityForm.dateAssessed', now()->addDay()->format('Y-m-d'))
            ->set('feasibilityForm.existingSolutionStatus', 'yes')
            ->set('feasibilityForm.existingSolutionNotes', 'Legacy System ABC exists')
            ->set('feasibilityForm.offTheShelfSolutionStatus', 'no')
            ->set('feasibilityForm.offTheShelfSolutionNotes', null)
            ->call('save', 'feasibility')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->feasibility->existing_solution_status)->toBe('yes')
            ->and($project->feasibility->existing_solution_notes)->toBe('Legacy System ABC exists')
            ->and($project->feasibility->off_the_shelf_solution_status)->toBe('no')
            ->and($project->feasibility->off_the_shelf_solution_notes)->toBeNull();
    });

    it('does not show approve/reject buttons when form is incomplete', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Ensure the feasibility is incomplete (default state from factory)
        $project->feasibility->update([
            'assessed_by' => null,
            'date_assessed' => null,
            'technical_credence' => null,
        ]);

        // Refresh to ensure changes are reflected
        $project->refresh();

        // Assert that isReadyForApproval is false
        expect($project->feasibility->isReadyForApproval())->toBeFalse();

        // Act & Assert - buttons should not exist
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="approve-feasibility-button"')
            ->assertDontSeeHtml('data-test="reject-feasibility-button"');
    });

    it('shows approve/reject buttons when form is complete and solution assessment provided', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Fill in all required fields AND solution assessment
        $project->feasibility->update([
            'assessed_by' => $assessor->id,
            'date_assessed' => now()->addDay(),
            'technical_credence' => 'Technically sound',
            'cost_benefit_case' => 'Good ROI',
            'dependencies_prerequisites' => 'None',
            'alternative_proposal' => 'No alternatives',
            'existing_solution_status' => 'no',
        ]);

        // Act & Assert - buttons should exist
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSeeHtml('data-test="approve-feasibility-button"')
            ->assertSeeHtml('data-test="reject-feasibility-button"');
    });

    it('isReadyForApproval returns false when required fields are missing', function () {
        // Arrange
        $project = Project::factory()->create();

        // Clear required fields
        $project->feasibility->update([
            'assessed_by' => null,
            'technical_credence' => null,
        ]);

        // Assert
        expect($project->feasibility->isReadyForApproval())->toBeFalse();
    });

    it('isReadyForApproval returns true when all required fields are filled', function () {
        // Arrange
        $assessor = User::factory()->create();
        $project = Project::factory()->create();

        // Assert - initially not ready
        expect($project->feasibility->isReadyForApproval())->toBeFalse();

        // Act - fill all required fields
        $project->feasibility->update([
            'assessed_by' => $assessor->id,
            'date_assessed' => now()->addDay(),
            'technical_credence' => 'Technically sound',
            'cost_benefit_case' => 'Good ROI',
            'dependencies_prerequisites' => 'None',
            'alternative_proposal' => 'No alternatives',
        ]);

        // Refresh to get updated data from database
        $project->feasibility->refresh();

        // Assert - now ready
        expect($project->feasibility->isReadyForApproval())->toBeTrue();
    });

    it('requires notes when yes_not_practical is selected for existing solution', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert - should have validation error
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.existingSolutionStatus', 'yes_not_practical')
            ->set('feasibilityForm.existingSolutionNotes', null)
            ->call('save', 'feasibility')
            ->assertHasErrors('feasibilityForm.existingSolutionNotes');
    });

    it('requires notes when yes_not_practical is selected for off-the-shelf solution', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert - should have validation error
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.offTheShelfSolutionStatus', 'yes_not_practical')
            ->set('feasibilityForm.offTheShelfSolutionNotes', null)
            ->call('save', 'feasibility')
            ->assertHasErrors('feasibilityForm.offTheShelfSolutionNotes');
    });

    it('allows yes_not_practical when notes are provided', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert - should save successfully
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('feasibilityForm.assessedBy', $assessor->id)
            ->set('feasibilityForm.dateAssessed', now()->addDay()->format('Y-m-d'))
            ->set('feasibilityForm.technicalCredence', 'Technically feasible')
            ->set('feasibilityForm.costBenefitCase', 'Good ROI')
            ->set('feasibilityForm.dependenciesPrerequisites', 'None')
            ->set('feasibilityForm.alternativeProposal', 'No alternatives')
            ->set('feasibilityForm.existingSolutionStatus', 'yes_not_practical')
            ->set('feasibilityForm.existingSolutionNotes', 'Too expensive for academic budget')
            ->call('save', 'feasibility')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->feasibility->existing_solution_status)->toBe('yes_not_practical')
            ->and($project->feasibility->existing_solution_notes)->toBe('Too expensive for academic budget');
    });

    it('blocks approval server-side when no solution assessment has been saved', function () {
        // Arrange - assessment complete but no solution assessment recorded
        Event::fake([FeasibilityApproved::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        completeSavedFeasibility($project, $assessor);
        $project->feasibility->update(['existing_solution_status' => null]);
        $this->actingAs($user);

        // Act - call approve directly, as a crafted request would (button is hidden in the UI)
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('approveFeasibility')
            ->assertHasErrors('feasibilityForm.approvalStatus');

        // Assert - approval did not happen and no event was dispatched
        $project->refresh();
        expect($project->feasibility->approval_status)->toBe('pending')
            ->and($project->feasibility->approved_at)->toBeNull();
        Event::assertNotDispatched(FeasibilityApproved::class);
    });

    it('does not show buttons when solution assessment is missing', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Fill in all required fields but NO solution assessment
        $project->feasibility->update([
            'assessed_by' => $assessor->id,
            'date_assessed' => now()->addDay(),
            'technical_credence' => 'Technically sound',
            'cost_benefit_case' => 'Good ROI',
            'dependencies_prerequisites' => 'None',
            'alternative_proposal' => 'No alternatives',
            'existing_solution_status' => null,
            'off_the_shelf_solution_status' => null,
        ]);

        // Act & Assert - buttons should NOT exist
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="approve-feasibility-button"')
            ->assertDontSeeHtml('data-test="reject-feasibility-button"');
    });
});
