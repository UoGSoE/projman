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
use App\Support\HeatmapCell;
use App\Traits\HasHeatmapData;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
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

    public string $viewMode = 'days';

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
            'development.notes.user',
            'testing',
            'deployed',
            'build.notes.user',
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
        $this->authorize('saveForm', [$this->project, $formType]);

        $formName = ProjectStatus::from($formType)->getFormName();

        $this->$formName->validate();

        $this->$formName->save();

        $this->project->addHistory(Auth::user(), 'Saved '.$formType);

        Flux::toast('Work package saved', variant: 'success');
    }

    public function advanceToNextStage()
    {
        $this->authorize('update', $this->project);

        if ($this->project->status === ProjectStatus::CANCELLED || $this->project->status === ProjectStatus::COMPLETED) {
            Flux::toast('Work package is '.ucfirst($this->project->status->value).', cannot advance to next stage', variant: 'warning');

            return;
        }

        $this->project->advanceToNextStage();

        $this->project->addHistory(Auth::user(), 'Advanced to '.$this->project->status->value);

        Flux::toast('Work package advanced to '.ucfirst($this->project->status->value), variant: 'success');
    }

    public function saveAndAdvance(string $formType): void
    {
        $this->save($formType);
        $this->advanceToNextStage();
    }

    public function approveFeasibility(): void
    {
        $this->authorize('update', $this->project);
        $this->feasibilityForm->approve();
        Flux::toast('Feasibility approved successfully', variant: 'success');
    }

    public function rejectFeasibility(): void
    {
        $this->authorize('update', $this->project);
        $this->feasibilityForm->reject();
        $this->modal('reject-feasibility-modal')->close();
        Flux::toast('Feasibility rejected', variant: 'warning');
    }

    public function submitScoping(): void
    {
        $this->authorize('update', $this->project);
        $this->scopingForm->submit();
        Flux::toast('Scoping submitted to Work Package Assessors', variant: 'success');
    }

    public function submitSchedulingToDCGG(): void
    {
        $this->authorize('update', $this->project);
        $this->schedulingForm->submitToDCGG();
        Flux::toast('Scheduling submitted to Digital Change Governance Group', variant: 'success');
    }

    public function scheduleScheduling(): void
    {
        $this->authorize('update', $this->project);
        $this->schedulingForm->schedule();
        Flux::toast('Scheduling approved and scheduled', variant: 'success');
    }

    public function requestUAT(): void
    {
        $this->authorize('update', $this->project);
        $this->testingForm->requestUAT();
        Flux::toast('UAT testing requested - UAT Tester has been notified', variant: 'success');
    }

    public function requestServiceAcceptance(): void
    {
        $this->authorize('update', $this->project);
        $this->testingForm->requestServiceAcceptance();
        Flux::toast('Service Acceptance requested - Service Leads have been notified', variant: 'success');
    }

    public function submitTesting(): void
    {
        $this->authorize('update', $this->project);
        $this->testingForm->submit();
        $this->advanceToNextStage();
        Flux::toast('Testing complete - work package advanced to Deployed stage', variant: 'success');
    }

    public function acceptDeploymentService(): void
    {
        $this->authorize('update', $this->project);
        $this->deployedForm->acceptService();
        Flux::toast('Service Acceptance submitted - Service Leads have been notified', variant: 'success');
    }

    public function approveDeployment(): void
    {
        $this->authorize('update', $this->project);
        $this->deployedForm->approve();
        Flux::toast('Deployment approved - work package status set to Completed', variant: 'success');
    }

    public function addDevelopmentNote(): void
    {
        $this->authorize('update', $this->project);
        $this->developmentForm->addNote($this->project->development);
        Flux::modal('add-note-developmentForm')->close();
    }

    public function addBuildNote(): void
    {
        $this->authorize('update', $this->project);
        $this->buildForm->addNote($this->project->build);
        Flux::modal('add-note-buildForm')->close();
    }

    public function toggleHeatmap(): void
    {
        $this->showHeatmap = ! $this->showHeatmap;
    }

    #[Computed]
    public function heatmapData(): array
    {
        $buckets = $this->getDateBuckets();
        $assignedUserIds = $this->getAssignedStaffIds();

        $staff = $this->staffWithCellsForBuckets($buckets, $assignedUserIds, $this->project->id);
        $staff = $this->applyLivePreview($staff, $buckets, $assignedUserIds);

        $projects = $this->activeProjects();

        return [
            'buckets' => $buckets,
            'staff' => $staff,
            'projects' => $projects,
            'component' => $this,
            'hasAssignedStaff' => ! empty($assignedUserIds),
            'canModelInEditProject' => $this->previewState() !== null,
        ];
    }

    /**
     * Snapshot the in-edit project's state from the live forms (falling back
     * to the saved record for fields the user hasn't changed). Returns null
     * when anything required to model the project is missing.
     *
     * @return array{effort_days: int, start: Carbon, end: Carbon, user_ids: array<int>, people_count: int}|null
     */
    protected function previewState(): ?array
    {
        $effort = $this->scopingForm->estimatedEffort ?? $this->project->scoping?->estimated_effort;
        $start = $this->schedulingForm->estimatedStartDate ?? $this->project->scheduling?->estimated_start_date;
        $end = $this->schedulingForm->estimatedCompletionDate ?? $this->project->scheduling?->estimated_completion_date;
        $userIds = $this->getAssignedStaffIds();

        if (! $effort || ! $start || ! $end || empty($userIds)) {
            return null;
        }

        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end->copy() : Carbon::parse($end);

        return [
            'effort_days' => $effort->estimatedDays(),
            'start' => $start,
            'end' => $end,
            'user_ids' => $userIds,
            'people_count' => count($userIds),
        ];
    }

    /**
     * Overlay the in-edit project's projected per-day cost on top of the
     * base cells for every currently-selected staff member.
     */
    protected function applyLivePreview(SupportCollection $staff, array $buckets, array $assignedUserIds): SupportCollection
    {
        $preview = $this->previewState();

        if ($preview === null) {
            return $staff;
        }

        $duration = (int) $preview['start']->diffInWeekdays($preview['end']) + 1;

        $overlaps = array_map(
            fn ($bucket) => $preview['start']->lte($bucket['end']) && $preview['end']->gte($bucket['start']),
            $buckets
        );

        return $staff->map(function ($entry) use ($preview, $duration, $overlaps, $assignedUserIds) {
            if (! in_array($entry['user']->id, $assignedUserIds)) {
                return $entry;
            }

            $extra = Project::calculatePerDayCost(
                $entry['user'],
                $preview['effort_days'],
                $preview['people_count'],
                $duration,
            );

            $entry['cells'] = array_map(
                fn ($cell, $i) => $overlaps[$i]
                    ? new HeatmapCell($cell->utilisation + $extra)
                    : $cell,
                $entry['cells'],
                array_keys($entry['cells']),
            );

            return $entry;
        });
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

        return User::itStaff()
            ->when(
                strlen($searchTerm) > 1,
                fn ($query) => $query->where('surname', 'like', '%'.$searchTerm.'%')
            )
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
            return User::itStaff()
                ->orderBy('surname')
                ->orderBy('forenames')
                ->get()
                ->map(function ($user) {
                    $user->total_skill_score = 0;

                    return $user;
                });
        }

        // Get ALL staff users, not just those with matching skills.
        // Awareness-level ratings are excluded from scoring — staff often record
        // "Awareness" for skills they've only read about, which inflates matches.
        return User::itStaff()
            ->with(['skills' => function ($query) use ($requiredSkillIds) {
                $query->whereIn('skill_id', $requiredSkillIds)
                    ->wherePivot('skill_level', '!=', SkillLevel::AWARENESS->value);
            }])
            ->get()
            ->map(function ($user) {
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
