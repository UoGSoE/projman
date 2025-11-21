<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Livewire\Form;

class BuildForm extends Form
{
    public ?Project $project = null;

    public function setProject(Project $project)
    {
        $this->project = $project;
        // Load fields when they exist
    }

    public function save()
    {
        $this->project->build->update([
            // Save fields when they exist
        ]);
    }
}
