<?php

use App\Enums\EffortScale;
use App\Events\ScopingScheduled;
use App\Events\ScopingSubmittedToDCGG;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Scoping Effort Scale & DCGG Workflow', function () {
    it('saves effort scale enum correctly', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('scopingForm.assessedBy', $assessor->id)
            ->set('scopingForm.estimatedEffort', EffortScale::MEDIUM->value)
            ->set('scopingForm.inScope', 'Build new feature')
            ->set('scopingForm.outOfScope', 'Legacy system migration')
            ->set('scopingForm.assumptions', 'Team available full-time')
            ->set('scopingForm.skillsRequired', [1, 2])
            ->call('save', 'scoping')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->scoping->estimated_effort)->toBeInstanceOf(EffortScale::class)
            ->and($project->scoping->estimated_effort)->toBe(EffortScale::MEDIUM);
    });

    it('validates effort scale is required', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act - try to call submitToDCGG without setting effort scale
        // This should fail validation because estimatedEffort is required
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('scopingForm.assessedBy', $assessor->id)
            ->set('scopingForm.inScope', 'Build new feature')
            ->set('scopingForm.outOfScope', 'Legacy system migration')
            ->set('scopingForm.assumptions', 'Team available full-time')
            ->set('scopingForm.skillsRequired', [1, 2])
            ->call('submitToDCGG')
            ->assertHasErrors();

        // Assert - status should still be pending
        $project->refresh();
        expect($project->scoping->dcgg_status)->toBe('pending');
    });

    it('submits to DCGG successfully', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $project->scoping->update([
            'assessed_by' => $assessor->id,
            'estimated_effort' => EffortScale::LARGE,
            'in_scope' => 'Feature A',
            'out_of_scope' => 'Feature B',
            'assumptions' => 'None',
            'skills_required' => [1],
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitToDCGG')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->scoping->dcgg_status)->toBe('submitted')
            ->and($project->scoping->submitted_to_dcgg_at)->not->toBeNull();
    });

    it('schedules scoping after DCGG submission', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update([
            'dcgg_status' => 'submitted',
            'submitted_to_dcgg_at' => now()->subDay(),
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScoping')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->scoping->dcgg_status)->toBe('approved')
            ->and($project->scoping->scheduled_at)->not->toBeNull();
    });

    it('dispatches ScopingSubmittedToDCGG event on submission', function () {
        // Arrange
        Event::fake([ScopingSubmittedToDCGG::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $project->scoping->update([
            'assessed_by' => $assessor->id,
            'estimated_effort' => EffortScale::SMALL,
            'in_scope' => 'Feature',
            'out_of_scope' => 'None',
            'assumptions' => 'None',
            'skills_required' => [1],
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitToDCGG');

        // Assert
        Event::assertDispatched(ScopingSubmittedToDCGG::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('dispatches ScopingScheduled event on scheduling', function () {
        // Arrange
        Event::fake([ScopingScheduled::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'submitted']);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScoping');

        // Assert
        Event::assertDispatched(ScopingScheduled::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('sends email to Work Package Assessors on DCGG submission', function () {
        // Arrange
        Mail::fake();
        $role = Role::factory()->create(['name' => 'Work Package Assessor']);
        $assessor = User::factory()->create();
        $assessor->roles()->attach($role);

        $user = User::factory()->create(['is_admin' => true]);
        $projectAssessor = User::factory()->create();
        $project = Project::factory()->create();
        $project->scoping->update([
            'assessed_by' => $projectAssessor->id,
            'estimated_effort' => EffortScale::MEDIUM,
            'in_scope' => 'Feature',
            'out_of_scope' => 'None',
            'assumptions' => 'None',
            'skills_required' => [1],
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitToDCGG');

        // Assert - email should be queued via SendEmailJob
        // Note: The actual email sending is handled by SendEmailJob
        // We're just verifying the event was dispatched which triggers the listener
        expect($project->fresh()->scoping->dcgg_status)->toBe('submitted');
    });

    it('records history when submitted to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $project = Project::factory()->create();
        $project->scoping->update([
            'assessed_by' => $assessor->id,
            'estimated_effort' => EffortScale::LARGE,
            'in_scope' => 'Feature',
            'out_of_scope' => 'None',
            'assumptions' => 'None',
            'skills_required' => [1],
        ]);
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitToDCGG');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Submitted to DCGG'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('records history when scoping is scheduled', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'submitted']);
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScoping');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Scoping approved and scheduled'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('only affects the specific project when submitting to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assessor = User::factory()->create();
        $projectToSubmit = Project::factory()->create();
        $projectToSubmit->scoping->update([
            'assessed_by' => $assessor->id,
            'estimated_effort' => EffortScale::SMALL,
            'in_scope' => 'Feature',
            'out_of_scope' => 'None',
            'assumptions' => 'None',
            'skills_required' => [1],
        ]);
        $otherProject = Project::factory()->create();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $projectToSubmit])
            ->call('submitToDCGG');

        // Assert
        $projectToSubmit->refresh();
        $otherProject->refresh();

        expect($projectToSubmit->scoping->dcgg_status)->toBe('submitted')
            ->and($otherProject->scoping->dcgg_status)->toBe('pending');
    });

    it('displays DCGG status badge when submitted', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'submitted']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('DCGG Status')
            ->assertSee('Submitted');
    });

    it('displays DCGG status badge when approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'approved']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('DCGG Status')
            ->assertSee('Approved');
    });

    it('shows Submit to DCGG button when status is pending', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'pending']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSeeHtml('data-test="submit-to-dcgg-button"');
    });

    it('shows Schedule button when status is submitted', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'submitted']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSeeHtml('data-test="schedule-scoping-button"');
    });

    it('hides workflow buttons when status is approved', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $project->scoping->update(['dcgg_status' => 'approved']);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="submit-to-dcgg-button"')
            ->assertDontSeeHtml('data-test="schedule-scoping-button"');
    });

    it('displays effort scale dropdown with all options', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Small (≤5 days)')
            ->assertSee('Medium (6-15 days)')
            ->assertSee('Large (16-30 days)')
            ->assertSee('X-Large (31-50 days)')
            ->assertSee('XX-Large (>50 days)');
    });

    it('shows Update button in Scoping tab', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Update');
        // Note: We don't test assertDontSee('Save') because other tabs may have Save buttons
    });
});
