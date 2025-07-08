<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Ideation extends Form
{
    public array $availableStrategicInitiatives = [
        'thing' => 'description',
        'other' => 'Other',
        'something' => 'Something',
    ];

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $schoolGroup = '';

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $objective = '';

    #[Validate('required|string|max:2048')]
    public string $businessCase = '';

    #[Validate('required|string|max:2048')]
    public string $benefits = '';

    #[Validate('required|date|after:today')]
    public string $deadline = '';

    #[Validate('required|string')]
    public string $initiative = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Ideation saved', variant: 'success');
    }
}
