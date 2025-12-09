<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasNotes
{
    public string $newNote = '';

    public function addNote(Model $noteable): void
    {
        $this->validate(['newNote' => 'required|string|max:65535']);

        $noteable->notes()->create([
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';

        $noteable->load('notes.user');
    }
}
