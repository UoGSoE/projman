<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class UserViewer extends Component
{
    public User $user;

    public bool $showAllAssignments = false;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function render()
    {
        $user = $this->user->load([
            'roles:id,name',
            'skills:id,name',
            'projects' => fn ($query) => $query->with([
                'scheduling:id,project_id,cose_it_staff',
            ])->latest(),
        ]);

        $roles = $user->roles->sortBy('name')->values();
        $skills = $user->skills->sortBy('name')->values();
        $requestedProjects = $user->projects;

        $allAssignments = Project::query()
            ->with([
                'user:id,forenames,surname',
                'scheduling:id,project_id,cose_it_staff',
            ])
            ->whereHas(
                'scheduling',
                fn ($query) => $query->whereJsonContains('cose_it_staff', $user->id)
            )
            ->orderByDesc('created_at')
            ->get();

        $visibleAssignments = $this->showAllAssignments
            ? $allAssignments
            : $allAssignments->reject(
                fn (Project $project) => in_array($project->status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED], true)
            );

        return view('livewire.user-viewer', [
            'user' => $user,
            'roles' => $roles,
            'skills' => $skills,
            'requestedProjects' => $requestedProjects,
            'itAssignments' => $visibleAssignments,
            'allItAssignments' => $allAssignments,
        ]);
    }
}
