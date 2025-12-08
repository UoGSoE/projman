<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BuildForm extends Form
{
    public ?Project $project = null;

    #[Validate('nullable|string|max:65535')]
    public ?string $buildRequirements = null;

    public string $newNote = '';

    public function setProject(Project $project): void
    {
        $this->project = $project;
        $this->buildRequirements = $project->build->build_requirements;
    }

    public function save(): void
    {
        $this->project->build->update([
            'build_requirements' => $this->buildRequirements,
        ]);
    }

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|string|max:65535',
        ]);

        $this->project->build->notes()->create([
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';

        $this->project->build->load('notes');
    }
}
