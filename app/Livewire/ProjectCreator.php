<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProjectCreator extends Component
{
    #[Validate('required|string|max:255')]
    public ?string $projectName;

    public function render()
    {
        return view('livewire.project-creator');
    }

    public function save()
    {
        $this->validate();

        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => $this->projectName,
            'status' => \App\Enums\ProjectStatus::IDEATION,
        ]);

        $project->addHistory(Auth::user(), 'Created');

        return $this->redirect(route('project.edit', $project->id));
    }
}
