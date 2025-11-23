<?php

namespace App\Livewire\Forms;

use App\Models\Project;
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

    #[Validate('required|date')]
    public ?string $dateAssessed;

    public ?string $existingSolutionStatus = null;

    public ?string $existingSolutionNotes = null;

    public ?string $offTheShelfSolutionStatus = null;

    public ?string $offTheShelfSolutionNotes = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $rejectReason = null;

    #[Validate('in:pending,approved,rejected')]
    public string $approvalStatus = 'pending';

    public function rules(): array
    {
        $rules = [
            'existingSolutionStatus' => 'nullable|in:yes,no,yes_not_practical',
            'existingSolutionNotes' => 'nullable|string|max:10000',
            'offTheShelfSolutionStatus' => 'nullable|in:yes,no,yes_not_practical',
            'offTheShelfSolutionNotes' => 'nullable|string|max:10000',
        ];

        if ($this->existingSolutionStatus === 'yes_not_practical') {
            $rules['existingSolutionNotes'] = 'required|string|max:10000';
        }

        if ($this->offTheShelfSolutionStatus === 'yes_not_practical') {
            $rules['offTheShelfSolutionNotes'] = 'required|string|max:10000';
        }

        return $rules;
    }

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
        $this->existingSolutionStatus = $project->feasibility->existing_solution_status;
        $this->existingSolutionNotes = $project->feasibility->existing_solution_notes;
        $this->offTheShelfSolutionStatus = $project->feasibility->off_the_shelf_solution_status;
        $this->offTheShelfSolutionNotes = $project->feasibility->off_the_shelf_solution_notes;
        $this->rejectReason = $project->feasibility->reject_reason;
        $this->approvalStatus = $project->feasibility->approval_status ?? 'pending';
    }

    public function save()
    {
        $this->validate();

        $this->project->feasibility->update([
            'assessed_by' => $this->assessedBy,
            'date_assessed' => $this->dateAssessed,
            'technical_credence' => $this->technicalCredence,
            'cost_benefit_case' => $this->costBenefitCase,
            'dependencies_prerequisites' => $this->dependenciesPrerequisites,
            'deadlines_achievable' => $this->deadlinesAchievable === 'yes',
            'alternative_proposal' => $this->alternativeProposal,
            'existing_solution_status' => $this->existingSolutionStatus,
            'existing_solution_notes' => $this->existingSolutionNotes,
            'off_the_shelf_solution_status' => $this->offTheShelfSolutionStatus,
            'off_the_shelf_solution_notes' => $this->offTheShelfSolutionNotes,
            'reject_reason' => $this->rejectReason,
            'approval_status' => $this->approvalStatus,
        ]);
    }

    public function approve(): void
    {
        $this->validate([
            'existingSolutionStatus' => [
                function ($attribute, $value, $fail) {
                    if ($value === 'yes') {
                        $fail('Cannot approve when an existing UoG solution has been identified.');
                    }
                },
            ],
            'offTheShelfSolutionStatus' => [
                function ($attribute, $value, $fail) {
                    if ($value === 'yes') {
                        $fail('Cannot approve when an off-the-shelf solution has been identified.');
                    }
                },
            ],
        ]);

        $this->project->feasibility->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'actioned_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $this->approvalStatus = 'approved';

        $this->setProject($this->project->fresh());

        event(new \App\Events\FeasibilityApproved($this->project));
        event(new \App\Events\ProjectUpdated($this->project, 'Approved feasibility'));
    }

    public function reject(): void
    {
        $this->validate([
            'rejectReason' => 'required|string|max:5000',
        ]);

        $this->project->feasibility->update([
            'approval_status' => 'rejected',
            'reject_reason' => $this->rejectReason,
            'actioned_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $this->approvalStatus = 'rejected';

        $this->setProject($this->project->fresh());

        event(new \App\Events\FeasibilityRejected($this->project));
        event(new \App\Events\ProjectUpdated($this->project, 'Rejected feasibility'));
    }
}
