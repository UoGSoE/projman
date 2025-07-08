<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Development extends Form
{
    public array $availableDevelopers = [
        '1' => 'Alice',
        '2' => 'Bob',
        '3' => 'Charlie',
    ];

    public array $availableStatuses = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'code_review' => 'Code Review',
        'testing' => 'Testing',
        'completed' => 'Completed',
    ];

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $leadDeveloper = '';

    #[Validate('required|string|max:255')]
    public string $developmentTeam = '';

    #[Validate('required|string|max:1024')]
    public string $technicalApproach = '';

    #[Validate('required|string|max:2048')]
    public string $developmentNotes = '';

    #[Validate('required|url|max:255')]
    public string $repositoryLink = '';

    #[Validate('required|string')]
    public string $status = '';

    #[Validate('required|date|after:today')]
    public string $startDate = '';

    #[Validate('required|date|after:startDate')]
    public string $completionDate = '';

    #[Validate('string|max:1024')]
    public string $codeReviewNotes = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Development saved', variant: 'success');
    }
}
