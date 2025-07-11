<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class DetailedDesignForm extends Form
{
    #[Validate('required|integer|exists:users,id')]
    public ?int $designedBy = null;

    #[Validate('required|string|max:255')]
    public string $serviceFunction = '';

    #[Validate('required|string|max:2048')]
    public string $functionalRequirements = '';

    #[Validate('required|string|max:2048')]
    public string $nonFunctionalRequirements = '';

    #[Validate('required|url|max:255')]
    public string $hldDesignLink = '';

    #[Validate('required|string|max:255')]
    public string $approvalDelivery = '';

    #[Validate('required|string|max:255')]
    public string $approvalOperations = '';

    #[Validate('required|string|max:255')]
    public string $approvalResilience = '';

    #[Validate('required|string|max:255')]
    public string $approvalChangeBoard = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Detailed Design saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update detailed design record
        $project->detailedDesign()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'designed_by' => $this->designedBy,
                'service_function' => $this->serviceFunction,
                'functional_requirements' => $this->functionalRequirements,
                'non_functional_requirements' => $this->nonFunctionalRequirements,
                'hld_design_link' => $this->hldDesignLink,
                'approval_delivery' => $this->approvalDelivery,
                'approval_operations' => $this->approvalOperations,
                'approval_resilience' => $this->approvalResilience,
                'approval_change_board' => $this->approvalChangeBoard,
            ]
        );

        Flux::toast('Detailed Design saved', variant: 'success');
    }
}
