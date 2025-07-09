<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class TestingForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|integer|exists:users,id')]
    public ?int $testLead = null;

    #[Validate('required|string|max:255')]
    public string $serviceFunction = '';

    #[Validate('required|string|max:255')]
    public string $functionalTestingTitle = '';

    #[Validate('required|string|max:2048')]
    public string $functionalTests = '';

    #[Validate('required|string|max:255')]
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

    public function saveToDatabase($project)
    {
        // Create or update testing record
        $project->testing()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'deliverable_title' => $this->deliverableTitle,
                'test_lead' => $this->testLead,
                'service_function' => $this->serviceFunction,
                'functional_testing_title' => $this->functionalTestingTitle,
                'functional_tests' => $this->functionalTests,
                'non_functional_testing_title' => $this->nonFunctionalTestingTitle,
                'non_functional_tests' => $this->nonFunctionalTests,
                'test_repository' => $this->testRepository,
                'testing_sign_off' => $this->testingSignOff,
                'user_acceptance' => $this->userAcceptance,
                'testing_lead_sign_off' => $this->testingLeadSignOff,
                'service_delivery_sign_off' => $this->serviceDeliverySignOff,
                'service_resilience_sign_off' => $this->serviceResilienceSignOff,
            ]
        );

        Flux::toast('Testing saved', variant: 'success');
    }
}
