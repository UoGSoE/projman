<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserViewer extends Component
{
    public User $user;

    public bool $showAllAssignments = false;

    public function render(): View
    {
        $user = $this->user->load([
            'roles:id,name',
            'skills:id,name',
            'projects' => fn ($query) => $query->with([
                'scheduling:id,project_id,cose_it_staff',
            ])->latest(),
        ]);

        $assignments = Project::query()
            ->with([
                'user:id,forenames,surname',
                'scheduling:id,project_id,cose_it_staff',
            ])
            ->whereHas(
                'scheduling',
                fn ($query) => $query->whereJsonContains('cose_it_staff', $user->id)
            )
            ->when(
                ! $this->showAllAssignments,
                fn ($query) => $query->whereNotIn('status', [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED])
            )
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.user-viewer', [
            'user' => $user,
            'roles' => $user->roles->sortBy('name'),
            'skills' => $user->skills->sortBy('name'),
            'requestedProjects' => $user->projects,
            'itAssignments' => $assignments,
        ]);
    }
}
