<?php

namespace App\Livewire;

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

    /** @var Collection<int, \App\Models\Project> */
    public Collection $itAssignments;

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
        $this->itAssignments = $this->resolveItAssignments();
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

    public function skillLevelLabel(string $level): string
    {
        return SkillLevel::tryFrom($level)?->getDisplayName() ?? ucfirst($level);
    }

    public function skillLevelColor(string $level): string
    {
        return SkillLevel::tryFrom($level)?->getColor() ?? 'zinc';
    }
}
