<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use App\Traits\HasNotes;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BuildForm extends Form
{
    use HasNotes;

    public ?Project $project = null;

    #[Validate('nullable|string|max:65535')]
    public ?string $buildRequirements = null;

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
}
