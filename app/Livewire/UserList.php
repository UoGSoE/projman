<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public $sortOn = 'surname';

    public $sortDirection = 'asc';

    public $search = '';

    public $selectedUser = null;

    public $userRoles = [];

    public $availableRoles = [];

    public array $userAttributes = [
        'id' => null,
        'username' => '',
        'email' => '',
        'surname' => '',
        'forenames' => '',
        'is_admin' => false,
        'is_itstaff' => false,
    ];

    public function render()
    {
        return view('livewire.user-list', [
            'users' => $this->getUsers(),
        ]);
    }

    public function getUsers()
    {
        $search = $this->search;

        return User::with('roles')->withCount('roles')->orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn ($query) => $query->where(
                    fn ($query) => $query->where('surname', 'like', '%'.$search.'%')
                        ->orWhere('forenames', 'like', '%'.$search.'%')
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
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        Flux::toast('Admin status updated', variant: 'success');
    }

    public function toggleItStaff(User $user)
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->is_itstaff = ! $user->is_itstaff;
        $user->save();

        Flux::toast('IT staff status updated', variant: 'success');
    }

    public function openChangeUserRoleModal(User $user)
    {
        $this->selectedUser = $user->fresh(['roles']);
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();
        $this->availableRoles = Role::active()->get();
    }

    public function saveUserRoles()
    {
        if (! $this->selectedUser) {
            Flux::toast('No user selected', variant: 'danger');

            return;
        }

        // Ensure userRoles is an array
        $userRoles = is_array($this->userRoles) ? $this->userRoles : [];

        // Validate that all selected roles exist and are active
        $validRoles = Role::query()
            ->whereIn('name', $userRoles)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (count($validRoles) !== count($userRoles)) {
            Flux::toast('Some selected roles are invalid', variant: 'danger');

            return;
        }

        // Sync the user's roles (this will add/remove roles as needed)
        $this->selectedUser->roles()->sync($validRoles);

        // Refresh the user to get updated relationships from database
        $this->selectedUser = $this->selectedUser->fresh(['roles']);

        // Update the component state with fresh data
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();

        Flux::modal('change-user-role')->close();
        Flux::toast('User roles updated successfully', variant: 'success');
    }

    public function openUserModal(?User $user = null): void
    {
        $this->resetValidation();
        $this->reset('userAttributes');

        if ($user) {
            $this->userAttributes = $user->only(array_keys($this->userAttributes));
        }

        Flux::modal('user-form')->show();
    }

    public function saveUser(): void
    {
        $userId = $this->userAttributes['id'];

        $this->validate([
            'userAttributes.username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'userAttributes.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'userAttributes.surname' => 'required|string|max:255',
            'userAttributes.forenames' => 'required|string|max:255',
            'userAttributes.is_admin' => 'boolean',
            'userAttributes.is_itstaff' => 'boolean',
        ]);

        $attributes = Arr::except($this->userAttributes, 'id');
        $attributes['email'] = Str::lower($attributes['email']);

        if ($userId) {
            User::findOrFail($userId)->update($attributes);
            $message = 'User updated successfully';
        } else {
            User::create($attributes + [
                'is_staff' => true,
                'password' => Str::random(64),
            ]);
            $message = 'User created successfully';
        }

        Flux::modal('user-form')->close();
        Flux::toast($message, variant: 'success');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}
