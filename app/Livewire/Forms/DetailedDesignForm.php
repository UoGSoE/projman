<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class DetailedDesignForm extends Form
{
    public ?Project $project = null;

    #[Validate('required|integer|exists:users,id')]
    public ?int $designedBy = null;

    #[Validate('required|string|max:255')]
    public ?string $serviceFunction;

    #[Validate('required|string|max:2048')]
    public ?string $functionalRequirements;

    #[Validate('required|string|max:2048')]
    public ?string $nonFunctionalRequirements;

    #[Validate('required|url|max:255')]
    public ?string $hldDesignLink;

    #[Validate('required|string|max:255')]
    public ?string $approvalDelivery;

    #[Validate('required|string|max:255')]
    public ?string $approvalOperations;

    #[Validate('required|string|max:255')]
    public ?string $approvalResilience;

    #[Validate('required|string|max:255')]
    public ?string $approvalChangeBoard;

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->designedBy = $project->detailedDesign->designed_by;
        $this->serviceFunction = $project->detailedDesign->service_function;
        $this->functionalRequirements = $project->detailedDesign->functional_requirements;
        $this->nonFunctionalRequirements = $project->detailedDesign->non_functional_requirements;
        $this->hldDesignLink = $project->detailedDesign->hld_design_link;
        $this->approvalDelivery = $project->detailedDesign->approval_delivery;
        $this->approvalOperations = $project->detailedDesign->approval_operations;
        $this->approvalResilience = $project->detailedDesign->approval_resilience;
        $this->approvalChangeBoard = $project->detailedDesign->approval_change_board;
    }

    public function save()
    {
        $this->project->detailedDesign->update([
            'designed_by' => $this->designedBy,
            'service_function' => $this->serviceFunction,
            'functional_requirements' => $this->functionalRequirements,
            'non_functional_requirements' => $this->nonFunctionalRequirements,
            'hld_design_link' => $this->hldDesignLink,
            'approval_delivery' => $this->approvalDelivery,
            'approval_operations' => $this->approvalOperations,
            'approval_resilience' => $this->approvalResilience,
            'approval_change_board' => $this->approvalChangeBoard,
        ]);
    }
}
