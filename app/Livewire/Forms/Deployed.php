<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Deployed extends Form
{
    public array $availableEnvironments = [
        'development' => 'Development',
        'staging' => 'Staging',
        'production' => 'Production',
    ];

    public array $availableDeploymentStatuses = [
        'pending' => 'Pending',
        'deployed' => 'Deployed',
        'failed' => 'Failed',
        'rolled_back' => 'Rolled Back',
    ];

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $deployedBy = '';

    #[Validate('required|string')]
    public string $environment = '';

    #[Validate('required|string')]
    public string $deploymentStatus = 'pending';

    #[Validate('required|date')]
    public string $deploymentDate = '';

    #[Validate('required|string|max:255')]
    public string $version = '';

    #[Validate('required|url|max:255')]
    public string $deploymentUrl = '';

    #[Validate('string|max:2048')]
    public string $deploymentNotes = '';

    #[Validate('string|max:1024')]
    public string $rollbackPlan = '';

    #[Validate('string|max:1024')]
    public string $monitoringNotes = '';

    #[Validate('required|string|max:255')]
    public string $signOffBy = '';

    #[Validate('required|date')]
    public string $signOffDate = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Deployed saved', variant: 'success');
    }
}
