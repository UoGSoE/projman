<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class UserList extends Component
{
    use WithPagination;

    public $sortOn = 'surname';
    public $sortDirection = 'asc';
    public $search = '';
    public $selectedUser = null;
    public $userRoles = [];
    public $availableRoles = [];
    public $formModified = false;

    public function render()
    {
        return view('livewire.user-list', [
            'users' => $this->getUsers()
        ]);
    }

    public function getUsers()
    {
        $search = $this->search;

        return User::with('roles')->withCount('roles')->orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn($query) => $query->where(
                    fn($query) => $query->where('surname', 'like', '%' . $search . '%')
                        ->orWhere('forenames', 'like', '%' . $search . '%')
                )
            )
            ->paginate(10);
    }

    public function sort($column)
    {
        if ($this->sortOn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortOn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleAdmin(User $user)
    {
        // Additional validation to ensure only admins can modify admin status
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        Flux::toast('Admin status updated', variant: 'success');
    }

    public function openChangeUserRoleModal(User $user)
    {

        $this->markFormAsNotModified();

        // Refresh the user to get fresh data from database
        $this->selectedUser = $user->fresh(['roles']);

        // Get user's current roles from the fresh database data
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();
        // Get all available roles from the database
        $this->availableRoles = Role::active()->get();
    }

    public function saveUserRoles()
    {
        if (!$this->selectedUser) {
            return;
        }

        // Ensure userRoles is an array
        $userRoles = is_array($this->userRoles) ? $this->userRoles : [];

        // Validate that all selected roles exist and are active
        $validRoles = Role::whereIn('name', $userRoles)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (count($validRoles) !== count($userRoles)) {
            Flux::toast('Some selected roles are invalid', variant: 'error');
            return;
        }

        // Sync the user's roles (this will add/remove roles as needed)
        $this->selectedUser->roles()->sync($validRoles);

        // Refresh the user to get updated relationships from database
        $this->selectedUser = $this->selectedUser->fresh(['roles']);

        // Update the component state with fresh data
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();
        $this->markFormAsNotModified();

        Flux::modal('change-user-role')->close();
        Flux::toast('User roles updated successfully', variant: 'success');
    }

    public function resetChangeUserRoleModal()
    {
        if ($this->selectedUser) {
            $freshUser = $this->selectedUser->fresh(['roles']);
            $this->userRoles = $freshUser->roles->pluck('name')->toArray();
        } else {
            $this->userRoles = [];
        }
        $this->selectedUser = null;
        $this->markFormAsNotModified();
    }

    public function markFormAsModified()
    {
        $this->formModified = true;
    }

    public function markFormAsNotModified()
    {
        $this->formModified = false;
    }

    public function updatedSearch()
    {
        // Reset pagination when search changes
        $this->resetPage();
    }
}
