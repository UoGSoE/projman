<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public string $sortOn = 'surname';

    public string $sortDirection = 'asc';

    public string $search = '';

    public ?User $selectedUser = null;

    /** @var array<int, string> */
    public array $userRoles = [];

    /** @var Collection<int, Role> */
    public Collection $availableRoles;

    public array $userAttributes = [
        'id' => null,
        'username' => '',
        'email' => '',
        'surname' => '',
        'forenames' => '',
        'is_admin' => false,
        'is_itstaff' => false,
    ];

    public function mount(): void
    {
        $this->availableRoles = collect();
    }

    public function render(): View
    {
        return view('livewire.user-list', [
            'users' => $this->getUsers(),
        ]);
    }

    public function getUsers(): LengthAwarePaginator
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

    public function sort(string $column): void
    {
        if ($this->sortOn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortOn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleAdmin(User $user): void
    {
        if (! auth()->user()?->isAdmin() || $user->is(auth()->user())) {
            abort(403);
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        Flux::toast('Admin status updated', variant: 'success');
    }

    public function toggleItStaff(User $user): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $user->is_itstaff = ! $user->is_itstaff;
        $user->save();

        Flux::toast('IT staff status updated', variant: 'success');
    }

    public function openChangeUserRoleModal(User $user): void
    {
        $this->selectedUser = $user->fresh(['roles']);
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();
        $this->availableRoles = Role::active()->get();
    }

    public function saveUserRoles(): void
    {
        $this->validate([
            'userRoles' => ['array'],
            'userRoles.*' => [Rule::exists('roles', 'name')->where('is_active', true)],
        ]);

        $this->selectedUser->roles()->sync(
            Role::whereIn('name', $this->userRoles)->pluck('id')
        );

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
            $user = User::findOrFail($userId);

            if ($user->is(auth()->user())) {
                unset($attributes['is_admin']);
            }

            $user->update($attributes);
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}
