<?php

namespace App\Livewire;

use App\Models\Role;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class RolesList extends Component
{
    use WithPagination;

    public $sortOn = 'name';

    public $sortDirection = 'asc';

    public $search = '';

    public $selectedRole = null;

    public $isCreating = false;

    public $roleName = '';

    public $roleDescription = '';

    public $roleIsActive = false;

    public function render()
    {
        return view('livewire.roles-list', [
            'roles' => $this->getRoles(),
        ]);
    }

    public function getRoles()
    {
        $search = $this->search;

        return Role::with('users')->withCount('users')->orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
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

    public function openCreateRoleModal()
    {
        $this->selectedRole = null;
        $this->isCreating = true;
        $this->roleName = '';
        $this->roleDescription = '';
        $this->roleIsActive = false;
        Flux::modal('edit-role')->show();
    }

    public function openEditRoleModal(Role $role)
    {
        $this->isCreating = false;
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
        if (! $this->selectedRole) {
            return;
        }

        $role = $this->selectedRole->fresh(['users']);

        // Check if role is assigned to any users before deletion
        if ($role->users->count() > 0) {
            Flux::toast('Cannot delete role that is assigned to users', variant: 'error');

            return;
        }

        $role->delete();

        // Close modal and show success message
        Flux::modal('delete-role')->close();
        Flux::toast('Role deleted successfully', variant: 'success');

        // Reset the selected role
        $this->selectedRole = null;
    }

    public function saveEditRole()
    {
        // Determine validation rules based on create vs edit
        $validationRules = [
            'roleDescription' => 'required|string|min:3|max:1000',
            'roleIsActive' => 'boolean',
        ];

        if ($this->isCreating) {
            $validationRules['roleName'] = 'required|string|max:255|unique:roles,name';
        } else {
            $validationRules['roleName'] = 'required|string|max:255|unique:roles,name,'.$this->selectedRole->id;
        }

        $this->validate($validationRules);

        if ($this->isCreating) {
            // Create new role
            $role = Role::create([
                'name' => $this->roleName,
                'description' => $this->roleDescription,
                'is_active' => $this->roleIsActive,
            ]);

            Flux::toast('Role created successfully', variant: 'success');
        } else {
            // Update existing role
            $this->selectedRole->name = $this->roleName;
            $this->selectedRole->description = $this->roleDescription;
            $this->selectedRole->is_active = $this->roleIsActive;
            $this->selectedRole->save();

            Flux::toast('Role updated successfully', variant: 'success');
        }

        Flux::modal('edit-role')->close();
        $this->resetEditRoleModal();
    }

    public function resetEditRoleModal()
    {
        $this->selectedRole = null;
        $this->isCreating = false;
        $this->roleName = '';
        $this->roleDescription = '';
        $this->roleIsActive = false;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}
