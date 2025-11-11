<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FeasibilityForm extends Form
{
    public ?Project $project = null;

    #[Validate('required|string|max:2048')]
    public ?string $technicalCredence;

    #[Validate('required|string|max:2048')]
    public ?string $costBenefitCase;

    #[Validate('required|string|max:2048')]
    public ?string $dependenciesPrerequisites;

    #[Validate('required|string')]
    public ?string $deadlinesAchievable = 'no';

    #[Validate('required|string|max:2048')]
    public ?string $alternativeProposal;

    #[Validate('required|integer|exists:users,id')]
    public ?int $assessedBy = null;

    #[Validate('required|date|after:today')]
    public ?string $dateAssessed;

    #[Validate('nullable|string|max:10000')]
    public ?string $existingSolution = null;

    #[Validate('nullable|string|max:10000')]
    public ?string $offTheShelfSolution = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $rejectReason = null;

    #[Validate('in:pending,approved,rejected')]
    public string $approvalStatus = 'pending';

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->assessedBy = $project->feasibility->assessed_by;
        $this->dateAssessed = (string) $project->feasibility->date_assessed?->format('Y-m-d');
        $this->technicalCredence = $project->feasibility->technical_credence;
        $this->costBenefitCase = $project->feasibility->cost_benefit_case;
        $this->dependenciesPrerequisites = $project->feasibility->dependencies_prerequisites;
        $this->deadlinesAchievable = $project->feasibility->deadlines_achievable ? 'yes' : 'no';
        $this->alternativeProposal = $project->feasibility->alternative_proposal;
        $this->existingSolution = $project->feasibility->existing_solution;
        $this->offTheShelfSolution = $project->feasibility->off_the_shelf_solution;
        $this->rejectReason = $project->feasibility->reject_reason;
        $this->approvalStatus = $project->feasibility->approval_status ?? 'pending';
    }

    public function save()
    {
        $this->project->feasibility->update([
            'assessed_by' => $this->assessedBy,
            'date_assessed' => $this->dateAssessed,
            'technical_credence' => $this->technicalCredence,
            'cost_benefit_case' => $this->costBenefitCase,
            'dependencies_prerequisites' => $this->dependenciesPrerequisites,
            'deadlines_achievable' => $this->deadlinesAchievable === 'yes',
            'alternative_proposal' => $this->alternativeProposal,
            'existing_solution' => $this->existingSolution,
            'off_the_shelf_solution' => $this->offTheShelfSolution,
            'reject_reason' => $this->rejectReason,
            'approval_status' => $this->approvalStatus,
        ]);
    }
}
