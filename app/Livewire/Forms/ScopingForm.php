<?php

namespace App\Livewire\Forms;

use App\Enums\EffortScale;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

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

    public ?EffortScale $estimatedEffort = null;

    #[Validate('required|string|max:2048')]
    public ?string $inScope;

    #[Validate('required|string|max:2048')]
    public ?string $outOfScope;

    #[Validate('required|string|max:2048')]
    public ?string $assumptions;

    #[Validate('required|array|min:1')]
    public array $skillsRequired = [];

    public string $dcggStatus = 'pending';

    public ?Carbon $submittedToDcggAt = null;

    public ?Carbon $scheduledAt = null;

    public function rules(): array
    {
        return [
            'assessedBy' => 'required|integer|exists:users,id',
            'estimatedEffort' => ['required', Rule::enum(EffortScale::class)],
            'inScope' => 'required|string|max:2048',
            'outOfScope' => 'required|string|max:2048',
            'assumptions' => 'required|string|max:2048',
            'skillsRequired' => 'required|array|min:1',
            'dcggStatus' => 'in:pending,submitted,approved',
        ];
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->assessedBy = $project->scoping->assessed_by;
        $this->estimatedEffort = $project->scoping->estimated_effort;
        $this->inScope = $project->scoping->in_scope;
        $this->outOfScope = $project->scoping->out_of_scope;
        $this->assumptions = $project->scoping->assumptions;
        $this->skillsRequired = $project->scoping->skills_required ?? [];
        $this->dcggStatus = $project->scoping->dcgg_status ?? 'pending';
        $this->submittedToDcggAt = $project->scoping->submitted_to_dcgg_at;
        $this->scheduledAt = $project->scoping->scheduled_at;
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
            'dcgg_status' => $this->dcggStatus,
            'submitted_to_dcgg_at' => $this->submittedToDcggAt,
            'scheduled_at' => $this->scheduledAt,
        ]);
    }
}
