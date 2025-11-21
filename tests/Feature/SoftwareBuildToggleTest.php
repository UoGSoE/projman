<?php

use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Software Development vs Build Toggle', function () {
    beforeEach(function () {
        $this->fakeNotifications();

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
                'skills_required' => [1, 2],
            ]);
        };
    });

    describe('Field Persistence', function () {
        it('saves and loads checkbox correctly', function () {
            // Arrange
            $project = Project::factory()->create();
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
            $project = Project::factory()->create();

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
            $project = Project::factory()->create();
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Fieldset should have disabled attribute bound to false
            $response->assertSeeHtml('data-test="development-form-fieldset"')
                ->assertSeeHtml(':disabled="!$scopingForm->requiresSoftwareDev"')
                ->assertSee('This project does not require custom software development');
        });

        it('enables development form fieldset when checkbox checked', function () {
            // Arrange
            $project = Project::factory()->create();
            $project->scoping->update(['requires_software_dev' => true]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert - Fieldset should have disabled attribute bound to true
            $response->assertSeeHtml('data-test="development-form-fieldset"')
                ->assertDontSee('This project does not require custom software development');
        });

        it('shows explanatory callout when development is disabled', function () {
            // Arrange
            $project = Project::factory()->create();
            $project->scoping->update(['requires_software_dev' => false]);

            // Act
            $response = livewire(ProjectEditor::class, ['project' => $project]);

            // Assert
            $response->assertSee('This project does not require custom software development')
                ->assertSee('Fields are disabled');
        });
    });

    describe('UI Tab Visibility', function () {
        it('always shows both development and build tabs', function () {
            // Arrange
            $project = Project::factory()->create();

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
            $project = Project::factory()->create();

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
            $project = Project::factory()->create();
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
            $project = Project::factory()->create();

            // Act - Switch to scoping tab
            $response = livewire(ProjectEditor::class, ['project' => $project])
                ->set('tab', 'scoping');

            // Assert - Checkbox should be present
            $response->assertSeeHtml('data-test="requires-software-dev-checkbox"');
        });
    });

    describe('Project Isolation', function () {
        it('does not affect other projects when toggling checkbox', function () {
            // Arrange
            $assessor = User::factory()->create();
            $project1 = Project::factory()->create();
            $project2 = Project::factory()->create();

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
