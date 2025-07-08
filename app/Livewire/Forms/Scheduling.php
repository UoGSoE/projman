<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Scheduling extends Form
{
    public array $availableUsers = [
        '1' => 'Jenny',
        '2' => 'John',
        '3' => 'Sarah',
    ];

    public array $availablePriorities = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:1024')]
    public string $keySkills = '';

    #[Validate('string|max:1024')]
    public string $coseItStaff = '';

    #[Validate('required|date|after:today')]
    public string $estimatedStartDate = '';

    #[Validate('required|date|after:estimatedStartDate')]
    public string $estimatedCompletionDate = '';

    #[Validate('required|date|after:today')]
    public string $changeBoardDate = '';

    #[Validate('required|string')]
    public string $assignedTo = '';

    #[Validate('required|string')]
    public string $priority = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Scheduling saved', variant: 'success');
    }
}
