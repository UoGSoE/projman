<?php

namespace App\Livewire;

use Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NotesList extends Component
{
    public Model $noteable;

    #[Validate('required|string|max:65535')]
    public string $newNote = '';

    public function mount(Model $noteable): void
    {
        $this->noteable = $noteable;
    }

    public function addNote(): void
    {
        $this->validate();

        $this->noteable->notes()->create([
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';

        $this->noteable->load('notes.user');

        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.notes-list');
    }
}
