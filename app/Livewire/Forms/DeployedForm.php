<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DeployedForm extends Form
{
    public ?Project $project = null;

    // Deployment Lead & Service Info
    #[Validate('nullable|integer|exists:users,id')]
    public ?int $deploymentLeadId = null;

    public ?string $serviceFunction = null;

    #[Validate('nullable|string')]
    public ?string $system = null;

    // Live Functional Testing
    #[Validate('nullable|string')]
    public ?string $fr1 = null;

    #[Validate('nullable|string')]
    public ?string $fr2 = null;

    #[Validate('nullable|string')]
    public ?string $fr3 = null;

    // Live Non-Functional Testing
    #[Validate('nullable|string')]
    public ?string $nfr1 = null;

    #[Validate('nullable|string')]
    public ?string $nfr2 = null;

    #[Validate('nullable|string')]
    public ?string $nfr3 = null;

    // BAU / Operational
    #[Validate('nullable|string')]
    public ?string $bauOperationalWiki = null;

    // Service Handover - Service Resilience
    #[Validate('required|in:pending,approved,rejected')]
    public string $serviceResilienceApproval = 'pending';

    #[Validate('nullable|string')]
    public ?string $serviceResilienceNotes = null;

    // Service Handover - Service Operations
    #[Validate('required|in:pending,approved,rejected')]
    public string $serviceOperationsApproval = 'pending';

    #[Validate('nullable|string')]
    public ?string $serviceOperationsNotes = null;

    // Service Handover - Service Delivery
    #[Validate('required|in:pending,approved,rejected')]
    public string $serviceDeliveryApproval = 'pending';

    #[Validate('nullable|string')]
    public ?string $serviceDeliveryNotes = null;

    public array $availableApprovalStates = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function setProject(Project $project): void
    {
        $this->project = $project;
        $this->deploymentLeadId = $project->deployed->deployment_lead_id;
        $this->serviceFunction = $project->user->service_function ?? 'Not Set';
        $this->system = $project->deployed->system;
        $this->fr1 = $project->deployed->fr1;
        $this->fr2 = $project->deployed->fr2;
        $this->fr3 = $project->deployed->fr3;
        $this->nfr1 = $project->deployed->nfr1;
        $this->nfr2 = $project->deployed->nfr2;
        $this->nfr3 = $project->deployed->nfr3;
        $this->bauOperationalWiki = $project->deployed->bau_operational_wiki;
        $this->serviceResilienceApproval = $project->deployed->service_resilience_approval;
        $this->serviceResilienceNotes = $project->deployed->service_resilience_notes;
        $this->serviceOperationsApproval = $project->deployed->service_operations_approval;
        $this->serviceOperationsNotes = $project->deployed->service_operations_notes;
        $this->serviceDeliveryApproval = $project->deployed->service_delivery_approval;
        $this->serviceDeliveryNotes = $project->deployed->service_delivery_notes;
    }

    public function save(): void
    {
        $this->project->deployed->update([
            'deployment_lead_id' => $this->deploymentLeadId,
            'service_function' => $this->serviceFunction,
            'system' => $this->system,
            'fr1' => $this->fr1,
            'fr2' => $this->fr2,
            'fr3' => $this->fr3,
            'nfr1' => $this->nfr1,
            'nfr2' => $this->nfr2,
            'nfr3' => $this->nfr3,
            'bau_operational_wiki' => $this->bauOperationalWiki,
            'service_resilience_approval' => $this->serviceResilienceApproval,
            'service_resilience_notes' => $this->serviceResilienceNotes,
            'service_operations_approval' => $this->serviceOperationsApproval,
            'service_operations_notes' => $this->serviceOperationsNotes,
            'service_delivery_approval' => $this->serviceDeliveryApproval,
            'service_delivery_notes' => $this->serviceDeliveryNotes,
        ]);
    }
}
