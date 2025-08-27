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
        // Sanitize search input to prevent potential injection
        $search = $this->sanitizeSearchInput($this->search);

        // Update the search property with sanitized value
        $this->search = $search;

        return User::orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn($query) => $query->where(
                    fn($query) => $query->where('surname', 'like', '%' . $search . '%')
                        ->orWhere('forenames', 'like', '%' . $search . '%')
                )
            )
            ->paginate(10);
    }

    /**
     * Sanitize search input to prevent potential security issues
     */
    // TODO: Remove sanitizeSearchInput
    private function sanitizeSearchInput(string $input): string
    {
        // Remove any potentially dangerous characters and limit length
        $sanitized = preg_replace('/[^\w\s\-\.]/', '', trim($input));
        return Str::limit($sanitized, 100);
    }

    public function sort($column)
    {
        // Validate sort column to prevent injection
        $allowedColumns = ['surname', 'forenames', 'username', 'email', 'created_at'];
        if (!in_array($column, $allowedColumns)) {
            return;
        }

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
        $this->formModified = false;
        $this->selectedUser = $user;

        // Get user's current roles from the database
        $this->userRoles = $user->roles->pluck('name')->toArray();
        // Get all available roles from the database
        $this->availableRoles = Role::active()->pluck('name')->toArray();
    }

    public function toggleRole($roleName)
    {
        // Validate role name to prevent injection
        if (!is_string($roleName) || !in_array($roleName, $this->availableRoles)) {
            return;
        }

        if (in_array($roleName, $this->userRoles)) {
            // Remove role
            $this->userRoles = array_values(array_filter($this->userRoles, fn($g) => $g !== $roleName));
        } else {
            // Add role
            $this->userRoles = array_values([...$this->userRoles, $roleName]);
        }

        // Explicitly mark form as modified since the lifecycle hook might not trigger
        $this->formModified = true;
    }

    public function saveUserRoles()
    {
        if (!$this->selectedUser) {
            return;
        }

        // Validate that all selected roles exist and are active
        $validRoles = Role::whereIn('name', $this->userRoles)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (count($validRoles) !== count($this->userRoles)) {
            Flux::toast('Some selected roles are invalid', variant: 'error');
            return;
        }

        // Sync the user's roles (this will add/remove roles as needed)
        $this->selectedUser->roles()->sync($validRoles);

        // Refresh the user to get updated relationships
        $this->selectedUser->refresh();
        Flux::modal('change-user-role')->close();
        Flux::toast('User roles updated successfully', variant: 'success');
    }

    public function resetChangeUserRoleModal()
    {
        $this->selectedUser = null;
        $this->userRoles = [];
    }

    public function updatedUserRoles()
    {
        $this->formModified = true;
    }

    public function updatedSearch()
    {
        // Reset pagination when search changes
        $this->resetPage();
    }
}
