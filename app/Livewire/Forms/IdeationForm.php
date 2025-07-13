<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class IdeationForm extends Form
{
    public ?Project $project = null;

    public array $availableStrategicInitiatives = [
        'thing' => 'description',
        'other' => 'Other',
        'something' => 'Something',
    ];

    #[Validate('required|string|max:255')]
    public ?string $schoolGroup;

    #[Validate('required|string|max:255')]
    public ?string $objective;

    #[Validate('required|string|max:2048')]
    public ?string $businessCase;

    #[Validate('required|string|max:2048')]
    public ?string $benefits;

    #[Validate('required|date|after:today')]
    public ?string $deadline;

    #[Validate('required|string')]
    public ?string $initiative;

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->schoolGroup = $project->ideation->school_group;
        $this->objective = $project->ideation->objective;
        $this->businessCase = $project->ideation->business_case;
        $this->benefits = $project->ideation->benefits;
        $this->deadline = (string)$project->ideation->deadline?->format('Y-m-d');
        $this->initiative = (string)$project->ideation->strategic_initiative;
    }

    public function save()
    {
        $this->project->ideation->update([
            'school_group' => $this->schoolGroup,
            'objective' => $this->objective,
            'business_case' => $this->businessCase,
            'benefits' => $this->benefits,
            'deadline' => $this->deadline,
            'strategic_initiative' => $this->initiative,
        ]);
    }
}
