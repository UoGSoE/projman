<?php

namespace App\Livewire\Forms;

use App\Events\ProjectUpdated;
use App\Events\ServiceAcceptanceRequested;
use App\Events\UATRequested;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TestingForm extends Form
{
    public ?Project $project = null;

    #[Validate('required|integer|exists:users,id')]
    public ?int $testLead = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $uatTesterId = null;

    #[Validate('nullable|string|max:255')]
    public ?string $departmentOffice = null;

    #[Validate('required|string|max:255')]
    public ?string $serviceFunction;

    #[Validate('required|string|max:255')]
    public ?string $functionalTestingTitle;

    #[Validate('required|string|max:2048')]
    public ?string $functionalTests;

    #[Validate('required|string|max:255')]
    public ?string $nonFunctionalTestingTitle;

    #[Validate('required|string|max:2048')]
    public ?string $nonFunctionalTests;

    #[Validate('url|max:255')]
    public ?string $testRepository;

    #[Validate('required|string|max:255')]
    public ?string $testingSignOff = '';

    #[Validate('required|string|max:255')]
    public ?string $userAcceptance = '';

    #[Validate('required|string|max:255')]
    public ?string $testingLeadSignOff = '';

    #[Validate('required|string|max:255')]
    public ?string $serviceDeliverySignOff = '';

    #[Validate('required|string|max:255')]
    public ?string $serviceResilienceSignOff = '';

    #[Validate('nullable|string|max:5000')]
    public ?string $testingSignOffNotes = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $userAcceptanceNotes = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $testingLeadSignOffNotes = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $serviceDeliverySignOffNotes = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $serviceResilienceSignOffNotes = null;

    public $availableApprovalStates = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->testLead = $project->testing->test_lead;
        $this->uatTesterId = $project->testing->uat_tester_id;
        $this->departmentOffice = $project->testing->department_office;
        $this->serviceFunction = $project->testing->service_function;
        $this->functionalTestingTitle = $project->testing->functional_testing_title;
        $this->functionalTests = $project->testing->functional_tests;
        $this->nonFunctionalTestingTitle = $project->testing->non_functional_testing_title;
        $this->nonFunctionalTests = $project->testing->non_functional_tests;
        $this->testRepository = $project->testing->test_repository;
        $this->testingSignOff = $project->testing->testing_sign_off;
        $this->testingSignOffNotes = $project->testing->testing_sign_off_notes;
        $this->userAcceptance = $project->testing->user_acceptance;
        $this->userAcceptanceNotes = $project->testing->user_acceptance_notes;
        $this->testingLeadSignOff = $project->testing->testing_lead_sign_off;
        $this->testingLeadSignOffNotes = $project->testing->testing_lead_sign_off_notes;
        $this->serviceDeliverySignOff = $project->testing->service_delivery_sign_off;
        $this->serviceDeliverySignOffNotes = $project->testing->service_delivery_sign_off_notes;
        $this->serviceResilienceSignOff = $project->testing->service_resilience_sign_off;
        $this->serviceResilienceSignOffNotes = $project->testing->service_resilience_sign_off_notes;
    }

    public function save()
    {
        $this->project->testing->update([
            'test_lead' => $this->testLead,
            'uat_tester_id' => $this->uatTesterId,
            'department_office' => $this->departmentOffice,
            'service_function' => $this->serviceFunction,
            'functional_testing_title' => $this->functionalTestingTitle,
            'functional_tests' => $this->functionalTests,
            'non_functional_testing_title' => $this->nonFunctionalTestingTitle,
            'non_functional_tests' => $this->nonFunctionalTests,
            'test_repository' => $this->testRepository,
            'testing_sign_off' => $this->testingSignOff,
            'testing_sign_off_notes' => $this->testingSignOffNotes,
            'user_acceptance' => $this->userAcceptance,
            'user_acceptance_notes' => $this->userAcceptanceNotes,
            'testing_lead_sign_off' => $this->testingLeadSignOff,
            'testing_lead_sign_off_notes' => $this->testingLeadSignOffNotes,
            'service_delivery_sign_off' => $this->serviceDeliverySignOff,
            'service_delivery_sign_off_notes' => $this->serviceDeliverySignOffNotes,
            'service_resilience_sign_off' => $this->serviceResilienceSignOff,
            'service_resilience_sign_off_notes' => $this->serviceResilienceSignOffNotes,
        ]);
    }

    public function requestUAT(): void
    {
        $this->validate([
            'uatTesterId' => 'required|integer|exists:users,id',
        ]);

        $this->project->testing->update([
            'uat_tester_id' => $this->uatTesterId,
            'uat_requested_at' => now(),
        ]);

        $this->setProject($this->project->fresh());

        event(new UATRequested($this->project->fresh()));
        event(new ProjectUpdated($this->project, 'Requested UAT testing'));
    }

    public function requestServiceAcceptance(): void
    {
        $this->validate([
            'userAcceptance' => 'required|in:approved',
        ]);

        $this->project->testing->update([
            'service_acceptance_requested_at' => now(),
        ]);

        $this->setProject($this->project->fresh());

        event(new ServiceAcceptanceRequested($this->project));
        event(new ProjectUpdated($this->project, 'Requested Service Acceptance'));
    }

    public function submit(): void
    {
        $this->validate([
            'testingSignOff' => 'required|in:approved',
            'userAcceptance' => 'required|in:approved',
            'testingLeadSignOff' => 'required|in:approved',
            'serviceDeliverySignOff' => 'required|in:approved',
            'serviceResilienceSignOff' => 'required|in:approved',
        ]);

        event(new ProjectUpdated($this->project, 'Submitted testing - advancing to Deployed stage'));
    }
}
