<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class RolesList extends Component
{
    use WithPagination;

    public $sortOn = 'name';
    public $sortDirection = 'asc';
    public $search = '';
    public $selectedRole = null;

    // Form field properties
    public $roleName = '';
    public $roleDescription = '';
    public $roleIsActive = false;
    public $formModified = false;

    public function render()
    {
        return view('livewire.roles-list', [
            'roles' => $this->getRoles()
        ]);
    }

    public function getRoles()
    {
        $search = $this->search;

        return Role::with('users')->withCount('users')->orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                })
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

    public function openEditRoleModal(Role $role)
    {
        $this->selectedRole = $role;
        // Ensure the selectedRole is properly loaded with fresh data
        $this->selectedRole = $role->fresh();

        // Populate form fields
        $this->roleName = $this->selectedRole->name;
        $this->roleDescription = $this->selectedRole->description;
        $this->roleIsActive = $this->selectedRole->is_active;
    }

    public function openDeleteRoleModal(Role $role)
    {
        $this->selectedRole = $role->loadCount('users');
        // Open the delete confirmation modal
        Flux::modal('delete-role')->show();
    }

    public function deleteRole()
    {
        if (!$this->selectedRole) {
            return;
        }

        // Check if role is assigned to any users before deletion
        if ($this->selectedRole->users_count > 0) {
            Flux::toast('Cannot delete role that is assigned to users', variant: 'error');
            return;
        }

        $this->selectedRole->delete();

        // Close modal and show success message
        Flux::modal('delete-role')->close();
        Flux::toast('Role deleted successfully', variant: 'success');

        // Reset the selected role
        $this->selectedRole = null;
    }

    public function saveEditRole()
    {
        // Validate the form data
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,' . $this->selectedRole->id,
            'roleDescription' => 'required|string|min:3|max:1000',
            'roleIsActive' => 'boolean'
        ]);

        // Update the role with form data
        $this->selectedRole->name = $this->roleName;
        $this->selectedRole->description = $this->roleDescription;
        $this->selectedRole->is_active = $this->roleIsActive;
        $this->selectedRole->save();

        // Show success message
        Flux::toast('Role updated successfully', variant: 'success');
        Flux::modal('edit-role')->close();

        // Reset form fields and close modal
        $this->resetEditRoleModal();
    }

    public function resetEditRoleModal()
    {
        $this->selectedRole = null;
        $this->roleName = '';
        $this->roleDescription = '';
        $this->roleIsActive = false;
        $this->markFormAsNotModified();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['roleName', 'roleDescription', 'roleIsActive'])) {
            $this->markFormAsModified();
        }
    }

    public function updatedSearch()
    {
        // Reset pagination when search changes
        $this->resetPage();
    }

    public function markFormAsNotModified()
    {
        $this->formModified = false;
    }

    public function markFormAsModified()
    {
        $this->formModified = true;
    }
}
