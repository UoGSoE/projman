<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Testing extends Form
{
    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $testLead = '';

    #[Validate('required|string|max:255')]
    public string $serviceFunction = '';

    #[Validate('required|string|max:512')]
    public string $functionalTestingTitle = '';

    #[Validate('required|string|max:2048')]
    public string $functionalTests = '';

    #[Validate('required|string|max:512')]
    public string $nonFunctionalTestingTitle = '';

    #[Validate('required|string|max:2048')]
    public string $nonFunctionalTests = '';

    #[Validate('required|url|max:255')]
    public string $testRepository = '';

    #[Validate('required|string|max:255')]
    public string $testingSignOff = '';

    #[Validate('required|string|max:255')]
    public string $userAcceptance = '';

    #[Validate('required|string|max:255')]
    public string $testingLeadSignOff = '';

    #[Validate('required|string|max:255')]
    public string $serviceDeliverySignOff = '';

    #[Validate('required|string|max:255')]
    public string $serviceResilienceSignOff = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Testing saved', variant: 'success');
    }
}
