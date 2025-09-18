<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Livewire\Forms\DeployedForm;
use App\Livewire\Forms\DetailedDesignForm;
use App\Livewire\Forms\DevelopmentForm;
use App\Livewire\Forms\FeasibilityForm;
use App\Livewire\Forms\IdeationForm;
use App\Livewire\Forms\SchedulingForm;
use App\Livewire\Forms\ScopingForm;
use App\Livewire\Forms\TestingForm;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    public $userSearch = '';

    #[Url]
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

    public Collection $availableSkills;

    public function mount(Project $project)
    {
        $project->load([
            'user',
            'ideation',
            'feasibility',
            'scoping',
            'scheduling',
            'detailedDesign',
            'development',
            'testing',
            'deployed',
            'history',
        ]);
        $this->projectId = $project->id;
        $this->project = $project;

        foreach (ProjectStatus::getAllFormNames() as $formName) {
            $this->$formName->setProject($project);
        }

        $this->availableSkills = Skill::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.project-editor');
    }

    public function save($formType)
    {
        $formName = ProjectStatus::from($formType)->getFormName();

        $this->$formName->validate();

        $this->$formName->save();

        $this->project->addHistory(Auth::user(), 'Saved '.$formType);

        Flux::toast('Project saved', variant: 'success');
    }

    public function advanceToNextStage()
    {

        $this->project->advanceToNextStage();

        $this->project->addHistory(Auth::user(), 'Advanced to '.$this->project->status->value);

        Flux::toast('Project saved and advanced to '.ucfirst($this->project->status->value), variant: 'success');

    }

    #[Computed]
    public function availableUsers()
    {
        $searchTerm = $this->userSearch;

        return User::query()
            ->when(
                strlen($searchTerm) > 1,
                fn ($query) => $query->where('surname', 'like', '%'.$searchTerm.'%')
            )
            ->limit(20)
            ->get();
    }
}
