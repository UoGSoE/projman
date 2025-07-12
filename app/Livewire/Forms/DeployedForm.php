<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class DeployedForm extends Form
{
    public ?Project $project = null;

    public array $availableEnvironments = [
        'development' => 'Development',
        'staging' => 'Staging',
        'production' => 'Production',
    ];

    public array $availableStatuses = [
        'pending' => 'Pending',
        'deployed' => 'Deployed',
        'failed' => 'Failed',
        'rolled_back' => 'Rolled Back',
    ];

    #[Validate('required|integer|exists:users,id')]
    public ?int $deployedBy = null;

    #[Validate('required|string|max:255')]
    public ?string $environment;

    #[Validate('required|string|max:255')]
    public ?string $status;

    #[Validate('required|date')]
    public ?string $deploymentDate;

    #[Validate('required|string|max:255')]
    public ?string $version;

    #[Validate('required|url|max:255')]
    public ?string $productionUrl;

    #[Validate('nullable|string|max:2048')]
    public ?string $deploymentNotes;

    #[Validate('nullable|string|max:2048')]
    public ?string $rollbackPlan;

    #[Validate('nullable|string|max:2048')]
    public ?string $monitoringNotes;

    #[Validate('required|string|max:255')]
    public ?string $deploymentSignOff;

    #[Validate('required|string|max:255')]
    public ?string $operationsSignOff;

    #[Validate('required|string|max:255')]
    public ?string $userAcceptanceSignOff;

    #[Validate('required|string|max:255')]
    public ?string $serviceDeliverySignOff;

    #[Validate('required|string|max:255')]
    public ?string $changeAdvisorySignOff;

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->deployedBy = $project->deployed->deployed_by;
        $this->environment = $project->deployed->environment;
        $this->status = $project->deployed->status;
        $this->deploymentDate = $project->deployed->deployment_date;
        $this->version = $project->deployed->version;
        $this->productionUrl = $project->deployed->production_url;
        $this->deploymentNotes = $project->deployed->deployment_notes;
        $this->rollbackPlan = $project->deployed->rollback_plan;
        $this->monitoringNotes = $project->deployed->monitoring_notes;
        $this->deploymentSignOff = $project->deployed->deployment_sign_off;
        $this->operationsSignOff = $project->deployed->operations_sign_off;
        $this->userAcceptanceSignOff = $project->deployed->user_acceptance;
        $this->serviceDeliverySignOff = $project->deployed->service_delivery_sign_off;
        $this->changeAdvisorySignOff = $project->deployed->change_advisory_sign_off;
    }

    public function save()
    {
        $this->project->deployed->update([
            'deployed_by' => $this->deployedBy,
            'environment' => $this->environment,
            'status' => $this->status,
            'deployment_date' => $this->deploymentDate,
            'version' => $this->version,
            'production_url' => $this->productionUrl,
            'deployment_notes' => $this->deploymentNotes,
            'rollback_plan' => $this->rollbackPlan,
            'monitoring_notes' => $this->monitoringNotes,
            'deployment_sign_off' => $this->deploymentSignOff,
            'operations_sign_off' => $this->operationsSignOff,
            'user_acceptance' => $this->userAcceptanceSignOff,
            'service_delivery_sign_off' => $this->serviceDeliverySignOff,
            'change_advisory_sign_off' => $this->changeAdvisorySignOff,
        ]);
    }
}
