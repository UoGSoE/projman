<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DetailedDesignForm extends Form
{
    public ?Project $project = null;

    public $availableApprovalStates = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public $availableAgbStates = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'not_required' => 'Not Required',
    ];

    #[Validate('required|integer|exists:users,id')]
    public ?int $designedBy = null;

    #[Validate('required|string|max:255')]
    public ?string $serviceFunction;

    #[Validate('required|string|max:2048')]
    public ?string $functionalRequirements;

    #[Validate('required|string|max:2048')]
    public ?string $nonFunctionalRequirements;

    #[Validate('url|max:255')]
    public ?string $hldDesignLink;

    #[Validate('required|string|max:255')]
    public ?string $approvalDelivery = 'Pending';

    #[Validate('required|string|max:255')]
    public ?string $approvalOperations = 'Pending';

    #[Validate('required|string|max:255')]
    public ?string $approvalResilience = 'Pending';

    #[Validate('required|string|max:255')]
    public ?string $approvalAgb = 'Pending';

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->designedBy = $project->detailedDesign->designed_by;
        $this->serviceFunction = $project->detailedDesign->service_function;
        $this->functionalRequirements = $project->detailedDesign->functional_requirements;
        $this->nonFunctionalRequirements = $project->detailedDesign->non_functional_requirements;
        $this->hldDesignLink = $project->detailedDesign->hld_design_link;
        $this->approvalDelivery = $project->detailedDesign->approval_delivery ?? 'Pending';
        $this->approvalOperations = $project->detailedDesign->approval_operations ?? 'Pending';
        $this->approvalResilience = $project->detailedDesign->approval_resilience ?? 'Pending';
        $this->approvalAgb = $project->detailedDesign->approval_agb ?? 'Pending';
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
            'approval_agb' => $this->approvalAgb,
            'approval_change_board' => $this->approvalAgb,
        ]);
    }
}
