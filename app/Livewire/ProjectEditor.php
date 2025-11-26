<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
use App\Livewire\Forms\BuildForm;
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
use App\Traits\HasHeatmapData;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProjectEditor extends Component
{
    use HasHeatmapData;

    public IdeationForm $ideationForm;

    public FeasibilityForm $feasibilityForm;

    public ScopingForm $scopingForm;

    public SchedulingForm $schedulingForm;

    public DetailedDesignForm $detailedDesignForm;

    public DevelopmentForm $developmentForm;

    public TestingForm $testingForm;

    public DeployedForm $deployedForm;

    public BuildForm $buildForm;

    public $userSearch = '';

    #[Url]
    public $tab = 'ideation';

    public ?int $projectId = null;

    public ?Project $project = null;

    public ?string $projectName = null;

    public Collection $availableSkills;

    public bool $showHeatmap = false;

    public array $originalAssignedStaffIds = [];

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
            'build',
            'history',
        ]);
        $this->projectId = $project->id;
        $this->project = $project;

        foreach (ProjectStatus::getAllFormNames() as $formName) {
            $this->$formName->setProject($project);
        }

        $this->availableSkills = Skill::orderBy('name')->get();

        // Store original staff IDs for live busyness preview comparison
        $this->originalAssignedStaffIds = $this->collectSavedStaffIds();

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
        $this->feasibilityForm->approve();
        Flux::toast('Feasibility approved successfully', variant: 'success');
    }

    public function rejectFeasibility(): void
    {
        $this->feasibilityForm->reject();
        $this->modal('reject-feasibility-modal')->close();
        Flux::toast('Feasibility rejected', variant: 'warning');
    }

    public function submitScoping(): void
    {
        $this->scopingForm->submit();
        Flux::toast('Scoping submitted to Work Package Assessors', variant: 'success');
    }

    public function submitSchedulingToDCGG(): void
    {
        $this->schedulingForm->submitToDCGG();
        Flux::toast('Scheduling submitted to Digital Change Governance Group', variant: 'success');
    }

    public function scheduleScheduling(): void
    {
        $this->schedulingForm->schedule();
        Flux::toast('Scheduling approved and scheduled', variant: 'success');
    }

    public function requestUAT(): void
    {
        $this->testingForm->requestUAT();
        Flux::toast('UAT testing requested - UAT Tester has been notified', variant: 'success');
    }

    public function requestServiceAcceptance(): void
    {
        $this->testingForm->requestServiceAcceptance();
        Flux::toast('Service Acceptance requested - Service Leads have been notified', variant: 'success');
    }

    public function submitTesting(): void
    {
        $this->testingForm->submit();
        $this->advanceToNextStage();
        Flux::toast('Testing complete - project advanced to Deployed stage', variant: 'success');
    }

    public function acceptDeploymentService(): void
    {
        $this->deployedForm->acceptService();
        Flux::toast('Service Acceptance submitted - Service Leads have been notified', variant: 'success');
    }

    public function approveDeployment(): void
    {
        $this->deployedForm->approve();
        Flux::toast('Deployment approved - project status set to Completed', variant: 'success');
    }

    public function toggleHeatmap(): void
    {
        $this->showHeatmap = ! $this->showHeatmap;
    }

    #[Computed]
    public function heatmapData(): array
    {
        $days = $this->upcomingWorkingDays(10);

        // Collect assigned user IDs from scheduling form
        $assignedUserIds = $this->getAssignedStaffIds();

        // Calculate busyness adjustments for live preview
        $adjustments = $this->calculateBusynessAdjustments();

        $staff = $this->staffWithBusyness($days, $assignedUserIds, $adjustments);
        $projects = $this->activeProjects();

        return [
            'days' => $days,
            'staff' => $staff,
            'projects' => $projects,
            'component' => $this,
            'hasAssignedStaff' => ! empty($assignedUserIds),
        ];
    }

    protected function getAssignedStaffIds(): array
    {
        return collect([
            $this->schedulingForm->assignedTo,
            $this->schedulingForm->technicalLeadId,
            $this->schedulingForm->changeChampionId,
        ])
            ->merge($this->schedulingForm->coseItStaff ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Collect staff IDs from the saved scheduling record.
     */
    protected function collectSavedStaffIds(): array
    {
        if (! $this->project->scheduling) {
            return [];
        }

        return collect([
            $this->project->scheduling->assigned_to,
            $this->project->scheduling->technical_lead_id,
            $this->project->scheduling->change_champion_id,
        ])
            ->merge($this->project->scheduling->cose_it_staff ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Calculate busyness adjustments for live preview.
     *
     * Returns array of user_id => adjustment (+1 for newly selected, -1 for deselected).
     */
    protected function calculateBusynessAdjustments(): array
    {
        $currentIds = collect($this->getAssignedStaffIds());
        $originalIds = collect($this->originalAssignedStaffIds);

        // Newly selected (not in original): +1
        $newlySelected = $currentIds->diff($originalIds)->values();

        // Deselected (was in original, not in current): -1
        $deselected = $originalIds->diff($currentIds)->values();

        $adjustments = [];

        foreach ($newlySelected as $userId) {
            $adjustments[$userId] = 1;
        }

        foreach ($deselected as $userId) {
            $adjustments[$userId] = -1;
        }

        return $adjustments;
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

    #[Computed]
    public function deployedServiceFunction(): string
    {
        if ($this->deployedForm->deploymentLeadId) {
            $user = User::find($this->deployedForm->deploymentLeadId);

            return $user?->service_function?->label() ?? 'Not Set';
        }

        return $this->project->user->service_function?->label() ?? 'Not Set';
    }

    public function getUsersMatchedBySkills(array $requiredSkillIds): Collection
    {
        // If no skills required, return all staff sorted alphabetically by surname
        if (empty($requiredSkillIds)) {
            return User::where('is_staff', true)
                ->orderBy('surname')
                ->orderBy('forenames')
                ->get()
                ->map(function ($user) {
                    $user->total_skill_score = 0;

                    return $user;
                });
        }

        // Get ALL staff users, not just those with matching skills
        return User::where('is_staff', true)
            ->with(['skills' => function ($query) use ($requiredSkillIds) {
                // Eager load only the required skills for score calculation
                $query->whereIn('skill_id', $requiredSkillIds);
            }])
            ->get()
            ->map(function ($user) {
                // Calculate skill score (will be 0 for users with no matching skills)
                // Access pivot data directly to avoid N+1 queries
                $totalScore = $user->skills->sum(function ($skill) {
                    $level = SkillLevel::from($skill->pivot->skill_level);

                    return $level->getNumericValue();
                });
                $user->total_skill_score = $totalScore;

                return $user;
            })
            ->sortBy('forenames')
            ->sortBy('surname')
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
