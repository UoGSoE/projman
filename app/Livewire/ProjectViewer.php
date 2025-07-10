<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectViewer extends Component
{
    public ?Project $project = null;

    public function mount(Project $project)
    {
        $project->load(['history.user', 'user', 'ideation', 'feasibility', 'scoping', 'scheduling', 'detailedDesign', 'development', 'testing', 'deployed']);
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project-viewer');
    }
}
