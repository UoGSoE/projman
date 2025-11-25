<?php

use App\Events\SchedulingScheduled;
use App\Events\SchedulingSubmittedToDCGG;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Scheduling DCGG Workflow', function () {
    beforeEach(function () {
        // Fake notifications for this test suite (doesn't test notification behavior)
        $this->fakeNotifications();
    });

    it('submits scheduling to DCGG successfully', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'key_skills' => 'Laravel, PHP',
            'priority' => \App\Enums\Priority::PRIORITY_2->value,
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitSchedulingToDCGG')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->scheduling->submitted_to_dcgg_at)->not->toBeNull()
            ->and($project->scheduling->submitted_to_dcgg_by)->toBe($user->id)
            ->and($project->scheduling->scheduled_at)->toBeNull();
    });

    it('validates required fields before submitting to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        // Leave scheduling fields empty
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitSchedulingToDCGG')
            ->assertHasErrors();
    });

    it('dispatches SchedulingSubmittedToDCGG event', function () {
        // Arrange
        Event::fake([SchedulingSubmittedToDCGG::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'key_skills' => 'Laravel, PHP',
            'priority' => \App\Enums\Priority::PRIORITY_2->value,
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitSchedulingToDCGG');

        // Assert
        Event::assertDispatched(SchedulingSubmittedToDCGG::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('records audit trail when submitting to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'key_skills' => 'Laravel, PHP',
            'priority' => \App\Enums\Priority::PRIORITY_2->value,
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitSchedulingToDCGG');

        // Assert
        $project->refresh();
        expect($project->scheduling->submitted_to_dcgg_by)->toBe($user->id)
            ->and($project->scheduling->submittedToDcggBy)->toBeInstanceOf(User::class)
            ->and($project->scheduling->submittedToDcggBy->id)->toBe($user->id);
    });

    it('records history when submitting to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'key_skills' => 'Laravel, PHP',
            'priority' => \App\Enums\Priority::PRIORITY_2->value,
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
        ]);
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('submitSchedulingToDCGG');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Submitted scheduling to DCGG'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('schedules scheduling successfully', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScheduling')
            ->assertHasNoErrors();

        // Assert
        $project->refresh();
        expect($project->scheduling->scheduled_at)->not->toBeNull();
    });

    it('validates Change Board date is required before scheduling', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => null, // Missing required field
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScheduling')
            ->assertHasErrors('schedulingForm.changeBoardDate');

        // Assert that scheduled_at was not set
        $project->refresh();
        expect($project->scheduling->scheduled_at)->toBeNull();
    });

    it('dispatches SchedulingScheduled event', function () {
        // Arrange
        Event::fake([SchedulingScheduled::class]);
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScheduling');

        // Assert
        Event::assertDispatched(SchedulingScheduled::class, function ($event) use ($project) {
            return $event->project->id === $project->id;
        });
    });

    it('records history when scheduling is scheduled', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $historyCountBefore = $project->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('scheduleScheduling');

        // Assert
        $project->refresh();
        expect($project->history()->count())->toBe($historyCountBefore + 1);

        $latestHistory = $project->history()->latest()->first();
        expect(str_contains($latestHistory->description, 'Scheduling approved and scheduled'))->toBeTrue()
            ->and($latestHistory->user_id)->toBe($user->id);
    });

    it('shows Submit to DCGG button when not yet submitted', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::factory()->create();
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSeeHtml('data-test="submit-scheduling-to-dcgg-button"');
    });

    it('hides Submit to DCGG button after submission', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="submit-scheduling-to-dcgg-button"');
    });

    it('shows Schedule button after DCGG submission but before scheduling', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
            'scheduled_at' => null,
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSeeHtml('data-test="schedule-scheduling-button"');
    });

    it('hides Schedule button after scheduling is complete', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $project = Project::factory()->create();
        $project->scheduling->update([
            'submitted_to_dcgg_at' => now()->subDays(2),
            'submitted_to_dcgg_by' => $assignedUser->id,
            'scheduled_at' => now()->subDay(),
        ]);
        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="schedule-scheduling-button"');
    });

    it('only affects the specific project when submitting to DCGG', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $projectToSubmit = Project::factory()->create();
        $projectToSubmit->scheduling->update([
            'key_skills' => 'Laravel, PHP',
            'priority' => \App\Enums\Priority::PRIORITY_2->value,
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
        ]);
        $otherProject = Project::factory()->create();
        $historyCountOther = $otherProject->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $projectToSubmit])
            ->call('submitSchedulingToDCGG');

        // Assert - other project was not affected
        $otherProject->refresh();
        expect($otherProject->history()->count())->toBe($historyCountOther)
            ->and($otherProject->scheduling->submitted_to_dcgg_at)->toBeNull()
            ->and($otherProject->scheduling->submitted_to_dcgg_by)->toBeNull();
    });

    it('only affects the specific project when scheduling', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $assignedUser = User::factory()->create();
        $projectToSchedule = Project::factory()->create();
        $projectToSchedule->scheduling->update([
            'assigned_to' => $assignedUser->id,
            'estimated_start_date' => now()->addDays(7),
            'estimated_completion_date' => now()->addDays(30),
            'change_board_date' => now()->addDays(5),
            'submitted_to_dcgg_at' => now()->subDay(),
            'submitted_to_dcgg_by' => $assignedUser->id,
        ]);
        $otherProject = Project::factory()->create();
        $historyCountOther = $otherProject->history()->count();
        $this->actingAs($user);

        // Act
        livewire(ProjectEditor::class, ['project' => $projectToSchedule])
            ->call('scheduleScheduling');

        // Assert - other project was not affected
        $otherProject->refresh();
        expect($otherProject->history()->count())->toBe($historyCountOther)
            ->and($otherProject->scheduling->scheduled_at)->toBeNull();
    });
});
