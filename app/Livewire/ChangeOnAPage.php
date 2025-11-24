<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ChangeOnAPage extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project->load([
            'user',
            'ideation',
            'feasibility',
            'scoping',
            'scheduling.assignedUser',
            'scheduling.technicalLead',
            'scheduling.changeChampion',
        ]);
    }

    public function render()
    {
        return view('livewire.change-on-a-page');
    }
}
