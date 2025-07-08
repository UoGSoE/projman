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
    public ?int $projectId = null;

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

        // Get or create project
        $project = $this->getOrCreateProject($formType);

        // Save the form with the project
        $this->$formName->saveToDatabase($project);
    }

    private function getOrCreateProject($formType)
    {
        if ($this->projectId) {
            return Project::find($this->projectId);
        }

        // Create new project for ideation, or create a minimal project for testing other forms
        if ($formType === 'ideation') {
            $project = Project::create([
                'user_id' => Auth::id(),
                'school_group' => $this->ideationForm->schoolGroup,
                'title' => $this->ideationForm->deliverableTitle,
                'deadline' => $this->ideationForm->deadline,
                'status' => 'ideation',
            ]);
            $this->projectId = $project->id;
            return $project;
        } else {
            // For testing purposes, create a minimal project if none exists
            if (!$this->projectId) {
                $project = Project::create([
                    'user_id' => Auth::id(),
                    'school_group' => 'Test School',
                    'title' => 'Test Project',
                    'deadline' => now()->addDays(30),
                    'status' => 'ideation',
                ]);
                $this->projectId = $project->id;
                return $project;
            }
            return Project::find($this->projectId);
        }
    }

    public function render()
    {
        Auth::loginUsingId(User::admin()->first()->id);
        return view('livewire.project-creator', [
            'project' => new Project(['user_id' => Auth::id()]),
        ]);
    }
}
