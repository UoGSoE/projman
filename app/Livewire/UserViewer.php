<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class UserViewer extends Component
{
    public User $user;

    #[Url]
    public bool $showAllAssignments = false;

    public function render(): View
    {
        $user = $this->user->load([
            'roles:id,name',
            'skills',
            'projects' => fn ($query) => $query->with([
                'scheduling:id,project_id,cose_it_staff',
            ])->latest(),
        ]);

        return view('livewire.user-viewer', [
            'user' => $user,
            'roles' => $user->roles->sortBy('name'),
            'skills' => $user->skills->sortBy('name'),
            'requestedProjects' => $user->projects,
            'itAssignments' => $user->itAssignments(includeCompleted: $this->showAllAssignments),
        ]);
    }
}
