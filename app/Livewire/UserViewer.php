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

        $allAssignments = $this->itAssignments($user);
        $visibleAssignments = $this->showAllAssignments
            ? $allAssignments
            : $allAssignments->reject(
                fn (Project $project) => in_array($project->status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED], true)
            );

        return view('livewire.user-viewer', [
            'user' => $user,
            'roles' => $user->roles->sortBy('name'),
            'skills' => $user->skills->sortBy('name'),
            'requestedProjects' => $user->projects,
            'itAssignments' => $visibleAssignments,
            'hadAnyAssignments' => $allAssignments->isNotEmpty(),
            'assignmentCountLabel' => $this->showAllAssignments ? 'total' : 'active',
        ]);
    }

    protected function itAssignments(User $user)
    {
        return Project::query()
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
    }
}
