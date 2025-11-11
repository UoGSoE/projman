<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
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

        // Update the CoSE IT staff field with skill-matched users
        // $this->updateCoseItStaffField();
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
        if ($this->project->status === ProjectStatus::CANCELLED || $this->project->status === ProjectStatus::COMPLETED) {
            Flux::toast('Project is '.ucfirst($this->project->status->value).', cannot advance to next stage', variant: 'warning');

            return;
        }

        $this->project->advanceToNextStage();

        $this->project->addHistory(Auth::user(), 'Advanced to '.$this->project->status->value);

        Flux::toast('Project saved and advanced to '.ucfirst($this->project->status->value), variant: 'success');

    }

    public function approveFeasibility(): void
    {
        if (! empty($this->feasibilityForm->existingSolution)) {
            Flux::toast('Cannot approve when an existing solution is identified. Please reject instead.', variant: 'danger');

            return;
        }

        $this->project->feasibility->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'actioned_by' => Auth::id(),
        ]);

        $this->feasibilityForm->approvalStatus = 'approved';

        $this->project->addHistory(Auth::user(), 'Approved feasibility');

        event(new \App\Events\FeasibilityApproved($this->project));

        Flux::toast('Feasibility approved successfully', variant: 'success');
    }

    public function rejectFeasibility(): void
    {
        $this->validate([
            'feasibilityForm.rejectReason' => 'required|string|max:5000',
        ]);

        $this->project->feasibility->update([
            'approval_status' => 'rejected',
            'reject_reason' => $this->feasibilityForm->rejectReason,
            'actioned_by' => Auth::id(),
        ]);

        $this->feasibilityForm->approvalStatus = 'rejected';

        $this->project->addHistory(Auth::user(), 'Rejected feasibility');

        event(new \App\Events\FeasibilityRejected($this->project));

        $this->modal('reject-feasibility-modal')->close();

        Flux::toast('Feasibility rejected', variant: 'warning');
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

    #[Computed]
    public function skillMatchedUsers()
    {
        $requiredSkillIds = $this->scopingForm->skillsRequired ?? [];

        if (empty($requiredSkillIds)) {
            return collect();
        }

        return $this->getUsersMatchedBySkills($requiredSkillIds);
    }

    public function getUsersMatchedBySkills(array $requiredSkillIds): Collection
    {
        if (empty($requiredSkillIds)) {
            return User::whereRaw('1 = 0')->get(); // Return empty Eloquent Collection
        }

        return User::with(['skills' => function ($query) use ($requiredSkillIds) {
            // eager load only skills with ids in the array requiredSkillIds for each user
            // this helps to not include skills we dont need to match
            $query->whereIn('skill_id', $requiredSkillIds);
        }])
            ->whereHas('skills', function ($query) use ($requiredSkillIds) {
                // whereHas filters the users to only include those with skills with ids in the array requiredSkillIds
                $query->whereIn('skill_id', $requiredSkillIds);
            })
            ->get()
            ->map(function ($user) {
                $totalScore = $user->skills->sum(function ($skill) use ($user) {
                    $level = SkillLevel::from($user->getSkillLevel($skill));

                    return $level->getNumericValue();
                });
                $user->total_skill_score = $totalScore;

                return $user;
            })
            ->sortByDesc('total_skill_score')
            ->values();
    }

    // public function updatedSkillMatchedUsers()
    // {
    //     $this->updateCoseItStaffField();
    // }

    // private function updateCoseItStaffField()
    // {
    //     $skillMatchedUsers = $this->skillMatchedUsers;

    //     if ($skillMatchedUsers->isNotEmpty()) {
    //         $requiredSkillIds = $this->scopingForm->skillsRequired ?? [];
    //         $totalRequired = is_array($requiredSkillIds) ? count($requiredSkillIds) : 0;

    //         $staffList = $skillMatchedUsers->map(function ($user) use ($requiredSkillIds, $totalRequired) {
    //             $userSkillIds = $user->skills->pluck('id')->toArray();
    //             $matchedCount = is_array($requiredSkillIds)
    //                 ? count(array_intersect($requiredSkillIds, $userSkillIds))
    //                 : 0;

    //             return $user->full_name.' - '.' ('.($matchedCount).'/'.($totalRequired).')'.' skills match';
    //         })->toArray();

    //         $this->schedulingForm->coseItStaff = $staffList;
    //     } else {
    //         $this->schedulingForm->coseItStaff = [];
    //     }
    // }
}
