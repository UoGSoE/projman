<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectViewer extends Component
{
    public ?Project $project = null;

    public function mount(Project $project)
    {
        $project->load('history.user');
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project-viewer');
    }
}
