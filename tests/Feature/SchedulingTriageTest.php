<?php

use App\Enums\ChangeBoardOutcome;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Scheduling Triage Fields', function () {
    beforeEach(function () {
        $this->fakeNotifications();

        // Helper method to set up valid scheduling data
        $this->setupValidScheduling = function (Project $project, User $assignedUser) {
            $project->scheduling->update([
                'key_skills' => 'Laravel, PHP',
                'priority' => \App\Enums\Priority::PRIORITY_2->value,
                'assigned_to' => $assignedUser->id,
                'estimated_start_date' => now()->addDays(7),
                'estimated_completion_date' => now()->addDays(30),
                'change_board_date' => now()->addDays(5),
            ]);
        };
    });

    describe('Field Persistence', function () {
        it('saves and loads technical lead correctly', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $technicalLead = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.technicalLeadId', $technicalLead->id)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->technical_lead_id)->toBe($technicalLead->id)
                ->and($project->scheduling->technicalLead->id)->toBe($technicalLead->id);
        });

        it('saves and loads change champion correctly', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $changeChampion = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.changeChampionId', $changeChampion->id)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->change_champion_id)->toBe($changeChampion->id)
                ->and($project->scheduling->changeChampion->id)->toBe($changeChampion->id);
        });

        it('saves and loads change board outcome correctly', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.changeBoardOutcome', ChangeBoardOutcome::APPROVED)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->change_board_outcome)
                ->toBe(ChangeBoardOutcome::APPROVED);
        });

        it('saves all three fields together', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $technicalLead = User::factory()->create();
            $changeChampion = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.technicalLeadId', $technicalLead->id)
                ->set('schedulingForm.changeChampionId', $changeChampion->id)
                ->set('schedulingForm.changeBoardOutcome', ChangeBoardOutcome::DEFERRED)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->technical_lead_id)->toBe($technicalLead->id)
                ->and($project->scheduling->change_champion_id)->toBe($changeChampion->id)
                ->and($project->scheduling->change_board_outcome)->toBe(ChangeBoardOutcome::DEFERRED);
        });

        it('accepts null values for all three fields', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act - Use empty string for enum (what dropdown sends), null for foreign keys
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.technicalLeadId', null)
                ->set('schedulingForm.changeChampionId', null)
                ->set('schedulingForm.changeBoardOutcome', '')
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->technical_lead_id)->toBeNull()
                ->and($project->scheduling->change_champion_id)->toBeNull()
                ->and($project->scheduling->change_board_outcome)->toBeNull();
        });
    });

    describe('Validation', function () {
        it('rejects invalid technical lead user id', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.technicalLeadId', 99999)
                ->call('save', 'scheduling')
                ->assertHasErrors(['schedulingForm.technicalLeadId']);
        });

        it('rejects invalid change champion user id', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.changeChampionId', 99999)
                ->call('save', 'scheduling')
                ->assertHasErrors(['schedulingForm.changeChampionId']);
        });

        it('accepts valid enum values', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('schedulingForm.changeBoardOutcome', ChangeBoardOutcome::REJECTED)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scheduling->change_board_outcome)->toBe(ChangeBoardOutcome::REJECTED);
        });

        it('requires valid enum values', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $project = Project::factory()->create();
            ($this->setupValidScheduling)($project, $assignedUser);
            $this->actingAs($user);

            // Act & Assert - Livewire enum hydration throws ValueError for invalid values
            expect(function () use ($project) {
                livewire(ProjectEditor::class, ['project' => $project])
                    ->set('schedulingForm.changeBoardOutcome', 'invalid-value');
            })->toThrow(ValueError::class);
        });
    });

    describe('UI Display', function () {
        it('displays technical lead dropdown in scheduling tab', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->assertSeeHtml('data-test="technical-lead-select"')
                ->assertSeeHtml('Technical Lead');
        });

        it('displays change champion dropdown in scheduling tab', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->assertSeeHtml('data-test="change-champion-select"')
                ->assertSeeHtml('Change Champion');
        });

        it('displays change board outcome dropdown in scheduling tab', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->assertSeeHtml('data-test="change-board-outcome-select"')
                ->assertSeeHtml('Change Board Outcome');
        });

        it('shows all change board outcome options', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $project = Project::factory()->create();
            $this->actingAs($user);

            // Act & Assert
            livewire(ProjectEditor::class, ['project' => $project])
                ->assertSee('Pending')
                ->assertSee('Approved')
                ->assertSee('Deferred')
                ->assertSee('Rejected');
        });

        it('displays selected values correctly', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $technicalLead = User::factory()->create(['forenames' => 'Tech', 'surname' => 'Lead']);
            $changeChampion = User::factory()->create(['forenames' => 'Change', 'surname' => 'Champion']);
            $project = Project::factory()->create();
            $project->scheduling->update([
                'technical_lead_id' => $technicalLead->id,
                'change_champion_id' => $changeChampion->id,
                'change_board_outcome' => ChangeBoardOutcome::APPROVED,
            ]);
            $this->actingAs($user);

            // Act & Assert
            $component = livewire(ProjectEditor::class, ['project' => $project]);

            expect($component->schedulingForm->technicalLeadId)->toBe($technicalLead->id)
                ->and($component->schedulingForm->changeChampionId)->toBe($changeChampion->id)
                ->and($component->schedulingForm->changeBoardOutcome)->toBe(ChangeBoardOutcome::APPROVED);
        });
    });

    describe('Relationships', function () {
        it('technicalLead relationship returns correct user', function () {
            // Arrange
            $technicalLead = User::factory()->create();
            $project = Project::factory()->create();
            $project->scheduling->update(['technical_lead_id' => $technicalLead->id]);

            // Act
            $result = $project->scheduling->technicalLead;

            // Assert
            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($technicalLead->id)
                ->and($result->email)->toBe($technicalLead->email);
        });

        it('changeChampion relationship returns correct user', function () {
            // Arrange
            $changeChampion = User::factory()->create();
            $project = Project::factory()->create();
            $project->scheduling->update(['change_champion_id' => $changeChampion->id]);

            // Act
            $result = $project->scheduling->changeChampion;

            // Assert
            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($changeChampion->id)
                ->and($result->email)->toBe($changeChampion->email);
        });
    });

    describe('Project Isolation', function () {
        it('updating one project does not affect other projects', function () {
            // Arrange
            $user = User::factory()->create(['is_admin' => true]);
            $assignedUser = User::factory()->create();
            $technicalLead1 = User::factory()->create();
            $technicalLead2 = User::factory()->create();
            $project1 = Project::factory()->create();
            $project2 = Project::factory()->create();
            ($this->setupValidScheduling)($project1, $assignedUser);
            ($this->setupValidScheduling)($project2, $assignedUser);
            $project2->scheduling->update(['technical_lead_id' => $technicalLead2->id]);
            $this->actingAs($user);

            // Act - Update project1
            livewire(ProjectEditor::class, ['project' => $project1])
                ->set('schedulingForm.technicalLeadId', $technicalLead1->id)
                ->call('save', 'scheduling')
                ->assertHasNoErrors();

            // Assert - project1 updated, project2 unchanged
            $project1->refresh();
            $project2->refresh();
            expect($project1->scheduling->technical_lead_id)->toBe($technicalLead1->id)
                ->and($project2->scheduling->technical_lead_id)->toBe($technicalLead2->id);
        });
    });
});
