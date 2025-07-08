<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class DetailedDesign extends Form
{
    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $designedBy = '';

    #[Validate('required|string|max:255')]
    public string $serviceFunction = '';

    #[Validate('required|string|max:4096')]
    public string $functionalRequirements = '';

    #[Validate('required|string|max:4096')]
    public string $nonFunctionalRequirements = '';

    #[Validate('required|url|max:255')]
    public string $hldDesignLink = '';

    #[Validate('string|max:255')]
    public string $approvalsHeader = 'Approvals';

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
}
