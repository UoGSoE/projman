<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use App\Models\User;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

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
    }
    public function save()
    {
        // Additional sanitization before saving
        $this->sanitizeInputs();

        $this->project->feasibility->update([
            'assessed_by' => $this->assessedBy,
            'date_assessed' => $this->dateAssessed,
            'technical_credence' => $this->technicalCredence,
            'cost_benefit_case' => $this->costBenefitCase,
            'dependencies_prerequisites' => $this->dependenciesPrerequisites,
            'deadlines_achievable' => $this->deadlinesAchievable === 'yes',
            'alternative_proposal' => $this->alternativeProposal,
        ]);
    }

    /**
     * Sanitize form inputs to prevent potential security issues
     */
    private function sanitizeInputs(): void
    {
        $this->technicalCredence = $this->sanitizeTextInput($this->technicalCredence);
        $this->costBenefitCase = $this->sanitizeTextInput($this->costBenefitCase);
        $this->dependenciesPrerequisites = $this->sanitizeTextInput($this->dependenciesPrerequisites);
        $this->alternativeProposal = $this->sanitizeTextInput($this->alternativeProposal);
    }

    /**
     * Sanitize text input to prevent potential security issues
     */
    private function sanitizeTextInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove HTML tags and limit length
        $sanitized = strip_tags(trim($input));
        return Str::limit($sanitized, 2048);
    }
}
