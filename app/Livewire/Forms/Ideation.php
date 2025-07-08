<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class Ideation extends Form
{
    public array $availableStrategicInitiatives = [
        'thing' => 'description',
        'other' => 'Other',
        'something' => 'Something',
    ];

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $schoolGroup = '';

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $objective = '';

    #[Validate('required|string|max:2048')]
    public string $businessCase = '';

    #[Validate('required|string|max:2048')]
    public string $benefits = '';

    #[Validate('required|date|after:today')]
    public string $deadline = '';

    #[Validate('required|string')]
    public string $initiative = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Ideation saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update ideation record
        $project->ideation()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'objective' => $this->objective,
                'business_case' => $this->businessCase,
                'benefits' => $this->benefits,
                'deadline' => $this->deadline,
                'strategic_initiative' => $this->initiative,
            ]
        );

        Flux::toast('Ideation saved', variant: 'success');
    }
}
