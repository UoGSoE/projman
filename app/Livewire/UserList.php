<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public $sortOn = 'surname';
    public $sortDirection = 'asc';
    public $search = '';

    public function render()
    {
        return view('livewire.user-list', [
            'users' => $this->getUsers()
        ]);
    }

    public function getUsers()
    {
        $search = trim(strtolower($this->search));
        return User::orderBy($this->sortOn, $this->sortDirection)
            ->when(
                strlen($search) >= 2,
                fn($query) => $query->where(
                    fn($query) => $query->where('surname', 'like', '%' . $search . '%')->orWhere('forenames', 'like', '%' . $search . '%')
                )
            )
            ->paginate(10);
    }

    public function sort($column) {
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
        $user->is_admin = ! $user->is_admin;
        $user->save();

        Flux::toast('Admin status updated', variant: 'success');
    }
}
