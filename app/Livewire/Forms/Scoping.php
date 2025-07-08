<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;

class Scoping extends Form
{
    public array $availableSkills = [
        'one' => 'Skill',
        'two' => 'Another Skill',
        'three' => 'Third Skill',
    ];

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:255')]
    public string $assessedBy = '';

    #[Validate('required|string|max:2048')]
    public string $estimatedEffort = '';

    #[Validate('required|string|max:2048')]
    public string $inScope = '';

    #[Validate('required|string|max:2048')]
    public string $outOfScope = '';

    #[Validate('required|string|max:2048')]
    public string $assumptions = '';

    #[Validate('required|string')]
    public string $skillsRequired = '';

    public function save()
    {
        $this->validate();

        Flux::toast('Scoping saved', variant: 'success');
    }
}
