<?php

namespace App\Livewire\Forms;

use App\Enums\ProjectStatus;
use App\Events\DeploymentApproved;
use App\Events\DeploymentServiceAccepted;
use App\Events\ProjectUpdated;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DeployedForm extends Form
{
    public ?Project $project = null;

    // Deployment Lead & Service Info
    #[Validate('nullable|integer|exists:users,id')]
    public ?int $deploymentLeadId = null;

    #[Validate('nullable|string|max:255')]
    public ?string $serviceFunction = null;

    // Live Functional Testing
    #[Validate('nullable|string')]
    public ?string $functionalTests = null;

    // Live Non-Functional Testing
    #[Validate('nullable|string')]
    public ?string $nonFunctionalTests = null;

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
        $this->serviceFunction = $project->deployed->service_function;
        $this->functionalTests = $project->deployed->functional_tests;
        $this->nonFunctionalTests = $project->deployed->non_functional_tests;
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
            'functional_tests' => $this->functionalTests,
            'non_functional_tests' => $this->nonFunctionalTests,
            'bau_operational_wiki' => $this->bauOperationalWiki,
            'service_resilience_approval' => $this->serviceResilienceApproval,
            'service_resilience_notes' => $this->serviceResilienceNotes,
            'service_operations_approval' => $this->serviceOperationsApproval,
            'service_operations_notes' => $this->serviceOperationsNotes,
            'service_delivery_approval' => $this->serviceDeliveryApproval,
            'service_delivery_notes' => $this->serviceDeliveryNotes,
        ]);
    }

    public function acceptService(): void
    {
        $this->validate([
            'deploymentLeadId' => 'required|integer|exists:users,id',
            'serviceFunction' => 'required|string',
            'functionalTests' => 'required|string',
            'nonFunctionalTests' => 'required|string',
            'bauOperationalWiki' => 'required|string',
        ]);

        $this->project->deployed->update([
            'service_accepted_at' => now(),
        ]);

        $this->setProject($this->project->fresh());

        event(new DeploymentServiceAccepted($this->project));
        event(new ProjectUpdated($this->project, 'Service Acceptance submitted'));
    }

    public function approve(): void
    {
        $this->validate([
            'serviceResilienceApproval' => 'required|in:approved',
            'serviceOperationsApproval' => 'required|in:approved',
            'serviceDeliveryApproval' => 'required|in:approved',
        ]);

        $this->project->deployed->update([
            'deployment_approved_at' => now(),
        ]);

        $this->project->update([
            'status' => ProjectStatus::COMPLETED,
        ]);

        $this->setProject($this->project->fresh());

        event(new DeploymentApproved($this->project));
        event(new ProjectUpdated($this->project, 'Deployment approved - project completed'));
    }
}
