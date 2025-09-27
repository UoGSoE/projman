<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Enums\SkillLevel;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class UserViewer extends Component
{
    public User $user;

    /** @var Collection<int, \App\Models\Project> */
    public Collection $requestedProjects;

    public bool $showAllAssignments = false;

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'roles:id,name',
            'skills' => fn ($query) => $query->orderBy('name'),
            'projects' => fn ($query) => $query->with([
                'scheduling:id,project_id,cose_it_staff',
            ])->latest(),
        ]);

        $this->requestedProjects = $this->user->projects;
    }

    public function render()
    {
        return view('livewire.user-viewer');
    }

    protected function resolveItAssignments(): Collection
    {
        if ($this->user->skills->isEmpty()) {
            return collect();
        }

        return Project::query()
            ->with([
                'user:id,forenames,surname',
                'scheduling:id,project_id,cose_it_staff',
            ])
            ->whereHas(
                'scheduling',
                fn ($query) => $query->whereJsonContains('cose_it_staff', $this->user->id)
            )
            ->orderByDesc('created_at')
            ->get();
    }

    public function getDisplayedItAssignmentsProperty(): Collection
    {
        $assignments = $this->allItAssignments;

        if ($this->showAllAssignments) {
            return $assignments;
        }

        return $assignments->reject(
            fn (Project $project) => in_array($project->status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED], true)
        );
    }

    public function getAllItAssignmentsProperty(): Collection
    {
        return once(fn () => $this->resolveItAssignments());
    }

    public function skillLevelLabel(string $level): string
    {
        return SkillLevel::tryFrom($level)?->getDisplayName() ?? ucfirst($level);
    }

    public function skillLevelColor(string $level): string
    {
        return SkillLevel::tryFrom($level)?->getColor() ?? 'zinc';
    }
}
