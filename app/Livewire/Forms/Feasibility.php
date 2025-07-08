<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Project;

class Feasibility extends Form
{
    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:2048')]
    public string $technicalCredence = '';

    #[Validate('required|string|max:2048')]
    public string $costBenefitCase = '';

    #[Validate('required|string|max:2048')]
    public string $dependenciesPrerequisites = '';

    #[Validate('required|string')]
    public string $deadlinesAchievable = '';

    #[Validate('required|string|max:2048')]
    public string $alternativeProposal = '';

    #[Validate('required|integer|exists:users,id')]
    public ?int $assessedBy = null;

    #[Validate('required|date|after:today')]
    public string $dateAssessed = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Feasibility saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update feasibility record
        $project->feasibility()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'assessed_by' => $this->assessedBy,
                'date_assessed' => $this->dateAssessed,
                'technical_credence' => $this->technicalCredence,
                'cost_benefit_case' => $this->costBenefitCase,
                'dependencies_prerequisites' => $this->dependenciesPrerequisites,
                'deadlines_achievable' => $this->deadlinesAchievable === 'yes',
                'alternative_proposal' => $this->alternativeProposal,
            ]
        );

        Flux::toast('Feasibility saved', variant: 'success');
    }
}
