<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class UserViewer extends Component
{
    public User $user;

    public Collection $requestedProjects;

    public bool $showAllAssignments = false;

    public Collection $skills;

    public Collection $roles;

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'roles:id,name',
            'skills:id,name',
            'projects' => fn ($query) => $query->with([
                'scheduling:id,project_id,cose_it_staff',
            ])->latest(),
        ]);

        $this->roles = $this->user->roles->sortBy('name')->values();
        $this->skills = $this->user->skills->sortBy('name')->values();
        $this->requestedProjects = $this->user->projects;
    }

    public function render()
    {
        $allAssignments = Project::query()
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

        $visibleAssignments = $this->showAllAssignments
            ? $allAssignments
            : $allAssignments->reject(
                fn (Project $project) => in_array($project->status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED], true)
            );

        return view('livewire.user-viewer', [
            'itAssignments' => $visibleAssignments,
            'allItAssignments' => $allAssignments,
        ]);
    }
}
