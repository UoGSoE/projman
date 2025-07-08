<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class Feasibility extends Form
{
    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|string|max:2048')]
    public string $technicalCredence = '';

    #[Validate('required|string|max:2048')]
    public string $costBenefitCase = '';

    #[Validate('required|string|max:2048')]
    public string $dependenciesPrerequisites = '';

    #[Validate('required|string')]
    public string $deadlinesAchievable = '';

    #[Validate('required|string|max:2048')]
    public string $alternativeProposal = '';

    #[Validate('required|string|max:255')]
    public string $assessedBy = '';

    #[Validate('required|date|after:today')]
    public string $dateAssessed = '';

}
