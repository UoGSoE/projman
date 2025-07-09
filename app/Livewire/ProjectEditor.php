<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use App\Livewire\Forms\IdeationForm;
use App\Livewire\Forms\FeasibilityForm;
use App\Livewire\Forms\ScopingForm;
use App\Livewire\Forms\SchedulingForm;
use App\Livewire\Forms\DetailedDesignForm;
use App\Livewire\Forms\DevelopmentForm;
use App\Livewire\Forms\TestingForm;
use App\Livewire\Forms\DeployedForm;

class ProjectEditor extends Component
{
    public IdeationForm $ideationForm;
    public FeasibilityForm $feasibilityForm;
    public ScopingForm $scopingForm;
    public SchedulingForm $schedulingForm;
    public DetailedDesignForm $detailedDesignForm;
    public DevelopmentForm $developmentForm;
    public TestingForm $testingForm;
    public DeployedForm $deployedForm;

    public $tab = 'ideation';
    public ?int $projectId = null;
    public ?Project $project = null;
    public ?string $projectName = null;

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

        // First run validation
        $this->$formName->validate();

        // Save the form with the project
        $this->$formName->saveToDatabase($this->project);
    }

    public function mount(Project $project)
    {
        $project->load(['ideation', 'feasibility', 'scoping', 'scheduling', 'detailedDesign', 'development', 'testing', 'deployed']);
        $this->projectId = $project->id;
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project-editor');
    }
}
