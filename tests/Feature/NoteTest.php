<?php

use App\Livewire\NotesList;
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

    describe('NotesList Component', function () {
        it('can add a note to development via NotesList component', function () {
            $project = Project::factory()->create();

            expect($project->development->notes)->toHaveCount(0);

            livewire(NotesList::class, ['noteable' => $project->development])
                ->set('newNote', 'My development progress update')
                ->call('addNote')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->development->notes)->toHaveCount(1)
                ->and($project->development->notes->first()->body)->toBe('My development progress update')
                ->and($project->development->notes->first()->user_id)->toBe($this->user->id);
        });

        it('can add a note to build via NotesList component', function () {
            $project = Project::factory()->create();

            expect($project->build->notes)->toHaveCount(0);

            livewire(NotesList::class, ['noteable' => $project->build])
                ->set('newNote', 'My build progress update')
                ->call('addNote')
                ->assertHasNoErrors();

            $project->refresh();
            expect($project->build->notes)->toHaveCount(1)
                ->and($project->build->notes->first()->body)->toBe('My build progress update')
                ->and($project->build->notes->first()->user_id)->toBe($this->user->id);
        });

        it('validates note body is required', function () {
            $project = Project::factory()->create();

            livewire(NotesList::class, ['noteable' => $project->development])
                ->set('newNote', '')
                ->call('addNote')
                ->assertHasErrors(['newNote']);

            expect($project->development->notes)->toHaveCount(0);
        });

        it('clears the note field after adding', function () {
            $project = Project::factory()->create();

            $component = livewire(NotesList::class, ['noteable' => $project->development])
                ->set('newNote', 'A note')
                ->call('addNote')
                ->assertHasNoErrors();

            expect($component->get('newNote'))->toBe('');
        });

        it('displays existing notes', function () {
            $project = Project::factory()->create();
            Note::factory()->forDevelopment($project->development)->create([
                'user_id' => $this->user->id,
                'body' => 'Existing development note',
            ]);
            $project->development->load('notes.user');

            livewire(NotesList::class, ['noteable' => $project->development])
                ->assertSee('Existing development note')
                ->assertSee($this->user->full_name);
        });

        it('displays Progress Notes heading', function () {
            $project = Project::factory()->create();

            livewire(NotesList::class, ['noteable' => $project->development])
                ->assertSee('Progress Notes');
        });

        it('shows empty state when no notes exist', function () {
            $project = Project::factory()->create();

            livewire(NotesList::class, ['noteable' => $project->development])
                ->assertSee('No notes yet.');
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
});
