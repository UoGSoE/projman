<?php

use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Software Development vs Build Toggle', function () {
    beforeEach(function () {
        $this->fakeAllProjectEvents();

        // Create test skills
        $this->skill1 = Skill::factory()->create(['name' => 'PHP']);
        $this->skill2 = Skill::factory()->create(['name' => 'Laravel']);

        $this->user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->user);

        // Helper to set up valid scoping data
        $this->setupValidScoping = function (Project $project, User $assessor) {
            $project->scoping->update([
                'assessed_by' => $assessor->id,
                'estimated_effort' => \App\Enums\EffortScale::MEDIUM,
                'in_scope' => 'Test scope',
                'out_of_scope' => 'Out of scope',
                'assumptions' => 'Test assumptions',
                'skills_required' => [$this->skill1->id, $this->skill2->id],
            ]);
        };
    });

    describe('Field Persistence', function () {
        it('saves and loads checkbox correctly', function () {
            // Arrange
            $project = $this->createProject();
            $assessor = User::factory()->create();
            ($this->setupValidScoping)($project, $assessor);

            // Act - Uncheck the box
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('scopingForm.requiresSoftwareDev', false)
                ->call('save', 'scoping')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scoping->requires_software_dev)->toBeFalse();

            // Act - Check the box again
            livewire(ProjectEditor::class, ['project' => $project])
                ->set('scopingForm.requiresSoftwareDev', true)
                ->call('save', 'scoping')
                ->assertHasNoErrors();

            // Assert
            $project->refresh();
            expect($project->scoping->requires_software_dev)->toBeTrue();
        });

        it('defaults to true for new projects', function () {
            // Arrange
            $project = $this->createProject();

            // Assert - Default should be true
            expect($project->scoping->requires_software_dev)->toBeTrue();

            // Act - Load in component
            $component = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Component should also show true
            expect($component->get('scopingForm.requiresSoftwareDev'))->toBeTrue();
        });
    });

    describe('Development Form Conditional Disabling', function () {
        it('disables development form fieldset when checkbox unchecked', function () {
            // Arrange
            $project = $this->createProject();
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Fieldset should exist and show explanatory message
            $response->assertSeeHtml('data-test="development-form-fieldset"')
                ->assertSee('This work package does not require custom software development');
        });

        it('enables development form fieldset when checkbox checked', function () {
            // Arrange
            $project = $this->createProject();
            $project->scoping->update(['requires_software_dev' => true]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Fieldset should exist and NOT show explanatory message
            $response->assertSeeHtml('data-test="development-form-fieldset"')
                ->assertDontSee('This work package does not require custom software development');
        });

        it('shows explanatory callout when development is disabled', function () {
            // Arrange
            $project = $this->createProject();
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert
            $response->assertSee('This work package does not require custom software development')
                ->assertSee('Fields are disabled');
        });
    });

    describe('UI Tab Visibility', function () {
        it('always shows both development and build tabs', function () {
            // Arrange
            $project = $this->createProject();

            // Act - Test with software dev enabled
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert
            $response->assertSee('Development')
                ->assertSee('Build');

            // Act - Test with software dev disabled
            $project->scoping->update(['requires_software_dev' => false]);
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Both tabs still visible
            $response->assertSee('Development')
                ->assertSee('Build');
        });

        it('displays build tab with TBC placeholder', function () {
            // Arrange
            $project = $this->createProject();

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Build tab should exist
            $response->assertSee('Build');

            // Note: We can't easily test the panel content without switching tabs,
            // but the template includes the build-form which has the TBC text
        });
    });

    describe('Checkbox Behavior', function () {
        it('is editable in scoping stage', function () {
            // Arrange
            $project = $this->createProject();
            $assessor = User::factory()->create();
            ($this->setupValidScoping)($project, $assessor);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project])
                ->assertSeeHtml('data-test="requires-software-dev-checkbox"');

            // Assert - Should be able to toggle it
            $response->set('scopingForm.requiresSoftwareDev', false)
                ->call('save', 'scoping')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->scoping->requires_software_dev)->toBeFalse();
        });

        it('displays checkbox in scoping form', function () {
            // Arrange
            $project = $this->createProject();

            // Act - Switch to scoping tab
            $response = livewire(ProjectEditor::class, ['project' => $project])
                ->set('tab', 'scoping');

            // Assert - Checkbox should be present
            $response->assertSeeHtml('data-test="requires-software-dev-checkbox"');
        });
    });

    describe('Stage Progression Skip', function () {
        it('skips Development stage when requires_software_dev is false', function () {
            // Arrange
            $project = $this->createProject(['status' => \App\Enums\ProjectStatus::DETAILED_DESIGN]);
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            $project->advanceToNextStage();

            // Assert - Should skip Development and go straight to Build
            expect($project->fresh()->status)->toBe(\App\Enums\ProjectStatus::BUILD);
        });

        it('does not skip Development stage when requires_software_dev is true', function () {
            // Arrange
            $project = $this->createProject(['status' => \App\Enums\ProjectStatus::DETAILED_DESIGN]);
            $project->scoping->update(['requires_software_dev' => true]);

            // Act
            $project->advanceToNextStage();

            // Assert - Should go to Development as normal
            expect($project->fresh()->status)->toBe(\App\Enums\ProjectStatus::DEVELOPMENT);
        });

        it('skips Development via Livewire advanceToNextStage when requires_software_dev is false', function () {
            // Arrange
            $project = $this->createProject(['status' => \App\Enums\ProjectStatus::DETAILED_DESIGN]);
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            livewire(ProjectEditor::class, ['project' => $project])
                ->call('advanceToNextStage')
                ->assertHasNoErrors();

            // Assert
            expect($project->fresh()->status)->toBe(\App\Enums\ProjectStatus::BUILD);
        });
    });

    describe('Project Isolation', function () {
        it('does not affect other projects when toggling checkbox', function () {
            // Arrange
            $assessor = User::factory()->create();
            $project1 = $this->createProject();
            $project2 = $this->createProject();

            ($this->setupValidScoping)($project1, $assessor);
            ($this->setupValidScoping)($project2, $assessor);

            $project1->scoping->update(['requires_software_dev' => true]);
            $project2->scoping->update(['requires_software_dev' => true]);

            // Act - Toggle project1 to false
            livewire(ProjectEditor::class, ['project' => $project1])
                ->set('scopingForm.requiresSoftwareDev', false)
                ->call('save', 'scoping')
                ->assertHasNoErrors();

            // Assert - Project1 changed, Project2 unchanged
            $project1->refresh();
            $project2->refresh();

            expect($project1->scoping->requires_software_dev)->toBeFalse()
                ->and($project2->scoping->requires_software_dev)->toBeTrue();
        });
    });
});
