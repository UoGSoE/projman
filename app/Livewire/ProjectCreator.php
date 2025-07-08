<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Project;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Forms\Ideation;
use App\Livewire\Forms\Feasibility;
use App\Livewire\Forms\Scoping;
use App\Livewire\Forms\Scheduling;
use App\Livewire\Forms\DetailedDesign;
use App\Livewire\Forms\Development;
use App\Livewire\Forms\Testing;
use App\Livewire\Forms\Deployed;

class ProjectCreator extends Component
{
    public Ideation $ideationForm;
    public Feasibility $feasibilityForm;
    public Scoping $scopingForm;
    public Scheduling $schedulingForm;
    public DetailedDesign $detailedDesignForm;
    public Development $developmentForm;
    public Testing $testingForm;
    public Deployed $deployedForm;

    public $tab = 'ideation';
    public ?int $formId = null;

    public $skills = [
        'one' => 'Skill',
    ];

    public $users = [
        '1' => 'Jenny',
    ];

    public function save($formType)
    {
        $formName = match($formType) {
            'ideation' => 'ideationForm',
            'feasibility' => 'feasibilityForm',
            'scoping' => 'scopingForm',
            'scheduling' => 'schedulingForm',
            'detailed-design' => 'detailedDesignForm',
            'development' => 'developmentForm',
            'testing' => 'testingForm',
            'deployed' => 'deployedForm',
        };

        $this->$formName->save();
    }

    public function render()
    {
        Auth::loginUsingId(User::where('name', 'admin')->first()->id);
        return view('livewire.project-creator', [
            'project' => new Project(['user_id' => Auth::id()]),
        ]);
    }
}
