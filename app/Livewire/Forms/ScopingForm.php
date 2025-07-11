<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class ScopingForm extends Form
{
    public array $availableSkills = [
        'one' => 'Skill',
        'two' => 'Another Skill',
        'three' => 'Third Skill',
    ];

    #[Validate('required|integer|exists:users,id')]
    public ?int $assessedBy = null;

    #[Validate('required|string|max:2048')]
    public string $estimatedEffort = '';

    #[Validate('required|string|max:2048')]
    public string $inScope = '';

    #[Validate('required|string|max:2048')]
    public string $outOfScope = '';

    #[Validate('required|string|max:2048')]
    public string $assumptions = '';

    #[Validate('required|string|max:255')]
    public string $skillsRequired = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Scoping saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update scoping record
        $project->scoping()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'assessed_by' => $this->assessedBy,
                'estimated_effort' => $this->estimatedEffort,
                'in_scope' => $this->inScope,
                'out_of_scope' => $this->outOfScope,
                'assumptions' => $this->assumptions,
                'skills_required' => $this->skillsRequired,
            ]
        );

        Flux::toast('Scoping saved', variant: 'success');
    }
}
