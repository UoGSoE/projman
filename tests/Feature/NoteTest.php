<?php

use App\Livewire\ProjectEditor;
use App\Models\Build;
use App\Models\Development;
use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Polymorphic Notes', function () {
    beforeEach(function () {
        $this->fakeNotifications();

        $this->user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->user);
    });

    describe('Note Model', function () {
        it('can be created on a Development model', function () {
            $project = Project::factory()->create();

            $note = Note::create([
                'noteable_type' => Development::class,
                'noteable_id' => $project->development->id,
                'user_id' => $this->user->id,
                'body' => 'This is a development progress note',
            ]);

            expect($note)->toBeInstanceOf(Note::class)
                ->and($note->noteable)->toBeInstanceOf(Development::class)
                ->and($note->body)->toBe('This is a development progress note');
        });

        it('can be created on a Build model', function () {
            $project = Project::factory()->create();

            $note = Note::create([
                'noteable_type' => Build::class,
                'noteable_id' => $project->build->id,
                'user_id' => $this->user->id,
                'body' => 'This is a build progress note',
            ]);

            expect($note)->toBeInstanceOf(Note::class)
                ->and($note->noteable)->toBeInstanceOf(Build::class)
                ->and($note->body)->toBe('This is a build progress note');
        });

        it('has correct user attribution', function () {
            $project = Project::factory()->create();

            $note = Note::create([
                'noteable_type' => Development::class,
                'noteable_id' => $project->development->id,
                'user_id' => $this->user->id,
                'body' => 'Test note',
            ]);

            expect($note->user->id)->toBe($this->user->id)
                ->and($note->user_name)->toBe($this->user->full_name);
        });

        it('returns System when user is deleted', function () {
            $project = Project::factory()->create();
            $tempUser = User::factory()->create();

            $note = Note::factory()->forDevelopment($project->development)->create([
                'user_id' => $tempUser->id,
            ]);

            // Simulate user deletion by setting to null after creation
            $note->user_id = null;
            $note->save();
            $note->refresh();

            expect($note->user_name)->toBe('System');
        });
    });

    describe('Development Notes via Livewire', function () {
        it('can add a note to development via livewire', function () {
            $project = Project::factory()->create();

            expect($project->development->notes)->toHaveCount(0);

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('developmentForm.newNote', 'My development progress update')
                ->call('addDevelopmentNote')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->development->notes)->toHaveCount(1)
                ->and($project->development->notes->first()->body)->toBe('My development progress update')
                ->and($project->development->notes->first()->user_id)->toBe($this->user->id);
        });

        it('validates note body is required', function () {
            $project = Project::factory()->create();

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('developmentForm.newNote', '')
                ->call('addDevelopmentNote')
                ->assertHasErrors(['developmentForm.newNote']);

            expect($project->development->notes)->toHaveCount(0);
        });

        it('clears the note field after adding', function () {
            $project = Project::factory()->create();

            $component = livewire(ProjectEditor::class, ['project' => $project])
                ->set('developmentForm.newNote', 'A note')
                ->call('addDevelopmentNote')
                ->assertHasNoErrors();

            expect($component->get('developmentForm.newNote'))->toBe('');
        });

        it('displays notes in the development form', function () {
            $project = Project::factory()->create();
            $note = Note::factory()->forDevelopment($project->development)->create([
                'user_id' => $this->user->id,
                'body' => 'Existing development note',
            ]);

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('tab', 'development')
                ->assertSee('Existing development note')
                ->assertSee($this->user->full_name);
        });
    });

    describe('Build Notes via Livewire', function () {
        it('can add a note to build via livewire', function () {
            $project = Project::factory()->create();

            expect($project->build->notes)->toHaveCount(0);

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('buildForm.newNote', 'My build progress update')
                ->call('addBuildNote')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->build->notes)->toHaveCount(1)
                ->and($project->build->notes->first()->body)->toBe('My build progress update')
                ->and($project->build->notes->first()->user_id)->toBe($this->user->id);
        });

        it('validates build note body is required', function () {
            $project = Project::factory()->create();

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('buildForm.newNote', '')
                ->call('addBuildNote')
                ->assertHasErrors(['buildForm.newNote']);

            expect($project->build->notes)->toHaveCount(0);
        });

        it('clears the build note field after adding', function () {
            $project = Project::factory()->create();

            $component = livewire(ProjectEditor::class, ['project' => $project])
                ->set('buildForm.newNote', 'A build note')
                ->call('addBuildNote')
                ->assertHasNoErrors();

            expect($component->get('buildForm.newNote'))->toBe('');
        });

        it('displays notes in the build form', function () {
            $project = Project::factory()->create();
            $note = Note::factory()->forBuild($project->build)->create([
                'user_id' => $this->user->id,
                'body' => 'Existing build note',
            ]);

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('tab', 'build')
                ->assertSee('Existing build note')
                ->assertSee($this->user->full_name);
        });
    });

    describe('Build Form', function () {
        it('can save build requirements field', function () {
            $project = Project::factory()->create();

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('buildForm.buildRequirements', 'These are the build requirements for this project.')
                ->call('save', 'build')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->build->build_requirements)->toBe('These are the build requirements for this project.');
        });

        it('loads build requirements from database', function () {
            $project = Project::factory()->create();
            $project->build->update(['build_requirements' => 'Pre-existing requirements']);

            $component = livewire(ProjectEditor::class, ['project' => $project]);

            expect($component->get('buildForm.buildRequirements'))->toBe('Pre-existing requirements');
        });

        it('displays build requirements textarea in form', function () {
            $project = Project::factory()->create();

            livewire(ProjectEditor::class, ['project' => $project])
                ->set('tab', 'build')
                ->assertSee('Build Requirements');
        });
    });

    describe('Notes Immutability', function () {
        it('notes have no update method exposed in livewire', function () {
            // Notes are immutable by design - there's no update endpoint
            // This test confirms that the Note model only has create, not update/delete actions
            $project = Project::factory()->create();

            $note = Note::factory()->forDevelopment($project->development)->create([
                'user_id' => $this->user->id,
                'body' => 'Original note',
            ]);

            // Confirm the note exists and cannot be modified through the UI
            // (There's no wire:click="editNote" or similar in the templates)
            expect($note->body)->toBe('Original note');

            // We can verify there's no method in ProjectEditor for editing/deleting notes
            $editor = new ProjectEditor;
            expect(method_exists($editor, 'editDevelopmentNote'))->toBeFalse()
                ->and(method_exists($editor, 'deleteDevelopmentNote'))->toBeFalse()
                ->and(method_exists($editor, 'editBuildNote'))->toBeFalse()
                ->and(method_exists($editor, 'deleteBuildNote'))->toBeFalse();
        });
    });
});
