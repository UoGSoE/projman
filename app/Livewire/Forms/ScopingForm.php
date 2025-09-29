<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class ScopingForm extends Form
{

    public ?Project $project = null;

    public array $availableSkills = [
        'one' => 'Skill',
        'two' => 'Another Skill',
        'three' => 'Third Skill',
    ];

    #[Validate('required|integer|exists:users,id')]
    public ?int $assessedBy;

    #[Validate('required|string|max:2048')]
    public ?string $estimatedEffort;

    #[Validate('required|string|max:2048')]
    public ?string $inScope;

    #[Validate('required|string|max:2048')]
    public ?string $outOfScope;

    #[Validate('required|string|max:2048')]
    public ?string $assumptions;

    #[Validate('required|array|min:1')]
    public array $skillsRequired = [];

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->assessedBy = $project->scoping->assessed_by;
        $this->estimatedEffort = $project->scoping->estimated_effort;
        $this->inScope = $project->scoping->in_scope;
        $this->outOfScope = $project->scoping->out_of_scope;
        $this->assumptions = $project->scoping->assumptions;
        // $temp = $project->scoping->skills_required;
        // dd($temp);
        // Convert stored JSON back to array, or use the string as single value for backward compatibility
        $this->skillsRequired = $project->scoping->skills_required ?? [];

    }

    public function save()
    {
        $this->project->scoping->update([
            'assessed_by' => $this->assessedBy,
            'estimated_effort' => $this->estimatedEffort,
            'in_scope' => $this->inScope,
            'out_of_scope' => $this->outOfScope,
            'assumptions' => $this->assumptions,
            'skills_required' => $this->skillsRequired,
        ]);
    }
}
