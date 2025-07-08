<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Project;
use Livewire\Component;
use App\Livewire\Forms\Ideation;
use App\Livewire\Forms\Feasibility;

class ProjectCreator extends Component
{
    public Ideation $ideationForm;
    public Feasibility $feasibilityForm;

    public $tab = 'ideation';
    public ?int $formId = null;

    public $skills = [
        'one' => 'Skill',
    ];

    public $users = [
        '1' => 'Jenny',
    ];

    public function render()
    {
        auth()->loginUsingId(User::admin()->first()->id);
        return view('livewire.project-creator', [
            'project' => new Project(['user_id' => auth()->user()->id]),
        ]);
    }
}
